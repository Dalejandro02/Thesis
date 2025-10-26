#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <math.h>

const char* ssid = "Proyecto_IOT";
const char* password = "Pr0yect010T";
const char* server = "http://192.168.128.77:8080/data_vb_cr42.php"; 

const int sensorPin = A0;
const float VCC = 3.3;
const int numMuestras = 1000;
const int numOffsetMuestras = 500;
const float sensibilidad = 0.066;

void conectarWiFi() {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.print("Reconectando a WiFi...");
        WiFi.begin(ssid, password);
        int intentos = 0;
        while (WiFi.status() != WL_CONNECTED && intentos < 20) {
            delay(500);
            Serial.print(".");
            intentos++;
        }
        if (WiFi.status() == WL_CONNECTED) {
            Serial.println(" ¡Reconectado!");
            Serial.print("Dirección IP: ");
            Serial.println(WiFi.localIP());
        } else {
            Serial.println(" No se pudo reconectar.");
        }
    }
}

void setup() {
    Serial.begin(115200);
    WiFi.begin(ssid, password);

    Serial.print("Conectando a WiFi...");
    while (WiFi.status() != WL_CONNECTED) {
        delay(1000);
        Serial.print(".");
    }
    Serial.println(" ¡Conectado!");
    Serial.print("Dirección IP asignada: ");
    Serial.println(WiFi.localIP());
}

void loop() {
    conectarWiFi();  // Verifica y reconecta si es necesario

    if (WiFi.status() == WL_CONNECTED) {
        WiFiClient client;
        HTTPClient http;

        float sumaOffset = 0.0;
        for (int i = 0; i < numOffsetMuestras; i++) {
            int lecturaADC = analogRead(sensorPin);
            float voltaje = (lecturaADC / 1023.0) * VCC;
            sumaOffset += voltaje;
            delayMicroseconds(500);
        }
        float offset = sumaOffset / numOffsetMuestras;

        Serial.print("Voltaje de referencia (Offset): ");
        Serial.println(offset, 3);

        float sumaCuadrados = 0.0;
        for (int i = 0; i < numMuestras; i++) {
            int lecturaADC = analogRead(sensorPin);
            float voltaje = (lecturaADC / 1023.0) * VCC;
            float corriente = (voltaje - offset) / sensibilidad;
            sumaCuadrados += (corriente * corriente);
            delayMicroseconds(100);
        }
        float corrienteRMS = sqrt(sumaCuadrados / numMuestras);

        Serial.print("Corriente RMS: ");
        Serial.print(corrienteRMS, 3);
        Serial.println(" A");

        String url = String(server) + "?VOLTAGE_REF=" + String(offset, 3) + "&AMPERAGE=" + String(corrienteRMS, 3);
        Serial.println("Enviando a: " + url);

        http.begin(client, url);
        int httpCode = http.GET();

        if (httpCode > 0) {
            String respuesta = http.getString();
            Serial.println("Respuesta del servidor: " + respuesta);
        } else {
            Serial.println("Error en la solicitud HTTP, código: " + String(httpCode));
        }

        http.end();
    }

    delay(20000);
}