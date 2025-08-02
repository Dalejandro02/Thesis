#include <SPI.h>
#include <MFRC522.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

const char* ssid = "Proyecto_IOT";
const char* password = "Pr0yect010T";
const char* dbUrl = "http://192.168.128.77:8080/db_access.php"; 
const char* controlUrls[] = {
  "http://172.31.0.111/activar",
  "http://172.31.0.112/activar",
  "http://172.31.0.113/activar"
};
const int delayLoop = 2;
String tagAnterior = "", serverDate = "", serverTime = "";
int totalPages = 1, serverMinutes = 0, horaInicioMin = 0, horaFinalMin = 0;
static bool tarjetaInvalida = false, retiroTarjeta = false, accesoPermitido = false; //tarjeta invalida es una variable que se utiliza en el loop de comparación
//esta variable determina si la tarjeta que se ingresó es valida o no, y redirecciona a los otros casos acorde a su valor.
//DEBE INICIAR EN FALSE, SI ESTA EN TRUE ES CON EL PROPOSITO DE PROBAR FUNCIONES. // retiroTarjeta es un booleano que muestra el estado anterior del sistema, esto para verificar si el sistema 
//antes de que cambiara estaba en una reserva activa o no true = retiro la tarjeta false = no ha retirado la tarjeta
// Configuración de pines
int horaInicio = 0, horaFinal = 0;

#define PIN_SDA  D8     // SDA del RC522
#define PIN_RST  D3     // RST del RC522
#define PIN_BOTON D0    // Pulsador activado por la tarjeta
#define buzzer D4

MFRC522 rfid(PIN_SDA, PIN_RST);

void iniciarApagado() {
  Serial.println("0");
}

void buzzSound(int repeticiones, int tiempoDelay) {
  for (int i = 0; i < repeticiones; i++) {
    digitalWrite(buzzer, LOW);
    delay(tiempoDelay);
    digitalWrite(buzzer, HIGH);
    delay(tiempoDelay);
  }
}

String leerUID() {
  rfid.PCD_Init();
  String uidTag = "";
  if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
    for (byte i = 0; i < rfid.uid.size; i++) {
      if (rfid.uid.uidByte[i] < 0x10) uidTag += "0";
      uidTag += String(rfid.uid.uidByte[i], HEX);
    }
    uidTag.toUpperCase();
    Serial.println("UID detectado: " + uidTag);
    rfid.PICC_HaltA();     // "Apaga" la tarjeta y la mantiene asi hasta que se retire y se vuelva a ubicar.
    rfid.PCD_StopCrypto1(); // Limpia estado interno
    delay(2000);
  }
  return uidTag;
}


void enviarActivacion(int estado) {
  if (estado == 0){
    Serial.println("0");
    delay(90000);
  }
  if (WiFi.status() != WL_CONNECTED) return;
  for (const char* url : controlUrls) {
    WiFiClient client;
    client.setTimeout(2); // 2 segundos
    HTTPClient http;
    http.begin(client, url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    http.POST("activacion=" + String(estado));
    http.end();
  }
}

void setup() {
  Serial.begin(115200);
  SPI.begin();
  rfid.PCD_Init();

  pinMode(buzzer, OUTPUT);
  digitalWrite(buzzer, HIGH);
  pinMode(PIN_BOTON, INPUT);  // Pulsador con resistencia

  // ------------------------------
  // Conexión a WiFi
  // ------------------------------
  Serial.println("Conectando al WiFi...");
  WiFi.begin(ssid, password);

  // esperar conexión
  int intentos = 0;
  while (WiFi.status() != WL_CONNECTED && intentos < 20) {
    delay(500);
    Serial.print(".");
    intentos++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi conectado!");
    Serial.print("Dirección IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nNo se pudo conectar al WiFi.");
  }
}


void loop() {

  //
  if ((digitalRead(PIN_BOTON) == LOW) && !tarjetaInvalida && !accesoPermitido) {  // Verifica si el botón está presionado (tarjeta presente). Este loop verifica si la tarjeta está en el sistema
    tarjetaInvalida = true;
    Serial.println("Pulsador presionado. ");
    String tag = leerUID();
    tagAnterior = tag;
    
    if (WiFi.status() == WL_CONNECTED) {
      for (int page = 1; page <= totalPages; page++) {
        String url = String(dbUrl) + "?page=" + page + "&limit=50";
        WiFiClient client;
        HTTPClient http;
        http.begin(client, url);
        int code = http.GET();
        if (code > 0) {
          DynamicJsonDocument doc(4096);
          if (!deserializeJson(doc, http.getString())) {
            if (page == 1) {
              totalPages = doc["total_pages"] | 1;
              serverDate = String(doc["server_date"]).substring(0, 10);
              serverTime = String(doc["server_date"]).substring(11, 16);
              int hour = serverTime.substring(0, 2).toInt();
              int minute = serverTime.substring(3, 5).toInt(); 
              serverMinutes = hour * 60 + minute;  
            }
            for (JsonObject item : doc["data"].as<JsonArray>()) {
              // Extraer horas como string
              String horaInicioStr = item["START_TIME"]; // ej. "09:00"
              String horaFinalStr  = item["END_TIME"];   // ej. "11:30"
            
              // Convertir a minutos desde medianoche
              horaInicioMin = horaInicioStr.substring(0, 2).toInt() * 60 +
                              horaInicioStr.substring(3, 5).toInt();
              horaFinalMin  = horaFinalStr.substring(0, 2).toInt() * 60 +
                              horaFinalStr.substring(3, 5).toInt();

              if (item["FACILITY_ID"] == "CR-4.4" &&
              item["DATE_"] == serverDate &&
              item["TAG"] == tag &&
              serverMinutes >= horaInicioMin &&
              serverMinutes < horaFinalMin) {
                accesoPermitido = true;
                tarjetaInvalida = false;
                Serial.println("xupame el huevo diego");
                return;
                //break;
              }
            }
          }
        }
        http.end();
        //if (accesoPermitido) break;
      }
    }
  }
//=========================================================================
//
//
//=========================================================================
else if ((digitalRead(PIN_BOTON) == LOW) && accesoPermitido) {
  Serial.println("Acceso permitido. Ejecutando activación...");
  enviarActivacion(1);  // activa los equipos
  tarjetaInvalida = false;
  accesoPermitido = false;
  int tiempoActual = serverMinutes;

  // While: mientras no se pase del tiempo final de reserva
  while (tiempoActual < horaFinalMin) {
    if (digitalRead(PIN_BOTON) == HIGH) {
      retiroTarjeta = true;
      Serial.println("Tarjeta retirada antes del fin de la reserva.");
      break;
    }
    Serial.println("Reserva activa...");
    delay(30000); // Espera 30 segundos antes de volver a consultar el tiempo

    // Actualizar hora actual desde el servidor
    if (WiFi.status() == WL_CONNECTED) {
      WiFiClient client;
      HTTPClient http;
      http.begin(client, dbUrl);
      int code = http.GET();
      if (code > 0) {
        DynamicJsonDocument doc(1024);
        if (!deserializeJson(doc, http.getString())) {
          String nuevaHora = String(doc["server_date"]).substring(11, 16);
          int hour = nuevaHora.substring(0, 2).toInt();
          int minute = nuevaHora.substring(3, 5).toInt(); 
          tiempoActual = hour * 60 + minute;
        }
      }
      http.end();
    }
  }
  if (retiroTarjeta) {
    retiroTarjeta = false;
    int contadorCiclo = 0;
    const int numeroCiclo = 12;
    const int delayCiclo = 5000; // en milisegundos, se repite el numero de veces que corra el ciclo, en caso de 12ciclos de 5s, seria 60 s
  
    while (digitalRead(PIN_BOTON) == HIGH) {
      Serial.println("Reinserte la tarjeta.");
      buzzSound(2, 200);
      delay(delayCiclo);
      if (contadorCiclo == (numeroCiclo - 1)) {
        Serial.println("Iniciando apagado.");
        enviarActivacion(0);
        return;
      }
      contadorCiclo += 1;
    }

    delay(5000); //dealy de 5 segundos para que se inserte la tarjeta correctamente.
    String nuevoTag = leerUID();

    if (nuevoTag == tagAnterior) {
      // tarjeta válida, continuar
      Serial.println("Tarjeta Valida Reinsertada");
      return;
    } else {
      Serial.println("Tarjeta invalida, apagando el sistema.");
      enviarActivacion(0);
    }
  }
  // Cuando se termina el tiempo de reserva o se retira la tarjeta
  Serial.println("Reserva finalizada.");
  enviarActivacion(0);  // desactiva los equipos
  return;
}
//=========================================================================
//
//
//=========================================================================
//=========================================================================
//Caso en el que la tarjeta es invalida y permanece en el lector.
//Cuando se quita la tarjeta invalida las variables se reinician.
//=========================================================================
  else if ((digitalRead(PIN_BOTON) == LOW) && tarjetaInvalida){
    const int delayCiclo = 2000;
    while (digitalRead(PIN_BOTON) == LOW) {
      buzzSound(2, 200);
      Serial.println("Tarjeta invalida, retirar su tarjeta. ");
      delay(delayCiclo);  
      }
    tarjetaInvalida = false;   
  }
//=========================================================================
//
//
//=========================================================================
//=========================================================================
//Este condicional a continuacion se encarga de verificar y corregir si una tarjeta que se ha retirado y se reinserta es la misma
//condicion en la que se daría continuidad al uso del sistema.
//=========================================================================
  else if ((digitalRead(PIN_BOTON) == HIGH) && retiroTarjeta) {
    retiroTarjeta = false;
    int contadorCiclo = 0;
    const int numeroCiclo = 12;
    const int delayCiclo = 5000; // en milisegundos, se repite el numero de veces que corra el ciclo, en caso de 12ciclos de 5s, seria 60 s
  
    while (digitalRead(PIN_BOTON) == HIGH) {
      Serial.println("Reinserte la tarjeta.");
      buzzSound(2, 200);
      delay(delayCiclo);
      if (contadorCiclo == (numeroCiclo - 1)) {
        Serial.println("Iniciando apagado.");
        iniciarApagado();
        return;
      }
      contadorCiclo += 1;
    }

    delay(5000); //dealy de 5 segundos para que se inserte la tarjeta correctamente.
    String nuevoTag = leerUID();

    if (nuevoTag == tagAnterior) {
      // tarjeta válida, continuar
      Serial.println("Tarjeta Valida Reinsertada");
      return;
    } else {
      Serial.println("Tarjeta invalida, apagando el sistema.");
      iniciarApagado();
    }
  }
//=========================================================================
//
//
//=========================================================================
  else {
   // Si el botón no está presionado
   Serial.println("No hay tarjeta. ");
   delay(delayLoop * 1000);
   return;
  }
}
//=========================================================================
//
//
//=========================================================================
 
