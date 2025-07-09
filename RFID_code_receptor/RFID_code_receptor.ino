#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>

const char* ssid = "Proyecto_IOT";
const char* password = "Pr0yect010T";

ESP8266WebServer server(80);

#define RELAY_ON 1
#define RELAY_OFF 0

// Configuración de IP fija
IPAddress local_IP(172, 31, 0, 143);       // IP fija del ESP en esa red
IPAddress gateway(172, 31, 0, 1);          // Puerta de enlace (consulta al administrador)
IPAddress subnet(255, 255, 255, 0);        // Máscara /24


bool servidorIniciado = false;
bool relesPorFalloWiFi = false;

void activarRelesPorDefecto() {
  digitalWrite(D1, RELAY_ON);
  digitalWrite(D5, RELAY_ON);
  digitalWrite(D6, RELAY_ON);
  digitalWrite(D7, RELAY_ON);
  Serial.println("WiFi no disponible. Relés activados por defecto.");
  relesPorFalloWiFi = true;
}

void manejarActivacion() {
  if (server.hasArg("activacion")) {
    int estado = server.arg("activacion").toInt();

    digitalWrite(D1, estado == 1 ? RELAY_ON : RELAY_OFF);
    digitalWrite(D5, estado == 1 ? RELAY_ON : RELAY_OFF);
    digitalWrite(D6, estado == 1 ? RELAY_ON : RELAY_OFF);
    digitalWrite(D7, estado == 1 ? RELAY_ON : RELAY_OFF);

    Serial.println(estado == 1 ? "Relés activados" : "Relés desactivados");
    relesPorFalloWiFi = false;

    server.send(200, "text/plain", "Recibido");
  } else {
    server.send(400, "text/plain", "Falta argumento 'activacion'");
  }
}

void setup() {
  Serial.begin(115200);

  pinMode(D1, OUTPUT);
  pinMode(D5, OUTPUT);
  pinMode(D6, OUTPUT);
  pinMode(D7, OUTPUT);

  digitalWrite(D1, RELAY_OFF);
  digitalWrite(D5, RELAY_OFF);
  digitalWrite(D6, RELAY_OFF);
  digitalWrite(D7, RELAY_OFF);

  WiFi.config(local_IP, gateway, subnet);
  WiFi.begin(ssid, password);
  Serial.print("Conectando a WiFi");

  unsigned long inicio = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - inicio < 10000) {
    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() != WL_CONNECTED) {
    activarRelesPorDefecto();
  } else {
    Serial.println("\nConectado. IP local: " + WiFi.localIP().toString());
    server.on("/activar", manejarActivacion);
    server.begin();
    servidorIniciado = true;
    Serial.println("Servidor HTTP iniciado");
  }
}

void loop() {
  if (WiFi.status() == WL_CONNECTED) {
    if (!servidorIniciado) {
      Serial.println("WiFi reconectado");
      server.on("/activar", manejarActivacion);
      server.begin();
      servidorIniciado = true;
      Serial.println("Servidor HTTP iniciado");
      if (relesPorFalloWiFi) {
        digitalWrite(D1, RELAY_OFF);
        digitalWrite(D5, RELAY_OFF);
        digitalWrite(D6, RELAY_OFF);
        digitalWrite(D7, RELAY_OFF);
        relesPorFalloWiFi = false;
        Serial.println("Relés apagados. Esperando nueva orden.");
      }
    }
    server.handleClient();
  } else {
    if (!relesPorFalloWiFi) {
      activarRelesPorDefecto();
    }

    static unsigned long ultimoIntento = 0;
    if (millis() - ultimoIntento > 5000) {
      Serial.println("WiFi desconectado. Reintentando conexión...");
      WiFi.begin(ssid, password);
      ultimoIntento = millis();

      if (servidorIniciado) {
        servidorIniciado = false;
        Serial.println("WiFi perdido. Servidor detenido.");
      }
    }
  }
}