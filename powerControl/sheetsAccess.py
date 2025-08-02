import serial
import time
from datetime import datetime
import subprocess

# Configurar los puertos seriales para la ESP8266 y el videobeam
try:
    esp_ser = serial.Serial(port='COM4', baudrate=115200, bytesize=serial.EIGHTBITS,
                            parity=serial.PARITY_NONE, stopbits=serial.STOPBITS_ONE, timeout=1)
    print("Conexión serial con ESP8266 establecida en COM4.")
except Exception as e:
    print(f"Error al configurar el puerto serial de la ESP8266: {e}")
    esp_ser = None


# Función para apagar el PC
def shutdown_pc():
    try:
        command = "powershell.exe -Command \"Start-Sleep -Seconds 5; Stop-Computer -Force\""
        subprocess.run(command, shell=True)
        print("PC apagado correctamente.")
    except Exception as e:
        print(f"Error intentando apagar el PC: {e}")


# Función para enviar comandos al videobeam
def send_videobeam_command(command):
    try:
        with serial.Serial(port='COM1', baudrate=9600, bytesize=serial.EIGHTBITS,
                               parity=serial.PARITY_NONE, stopbits=serial.STOPBITS_ONE, timeout=1) as vb_ser:
            vb_ser.write(command.encode())
            print(f"Comando enviado al videobeam:{command.strip()} ")
    except Exception as e:
         print(f"Error al enviar el comando {e}")


# Bucle principal para manejar ambos puertos
event_processed = False

while True:
    try:
        # Leer datos de la ESP8266
        if esp_ser and esp_ser.in_waiting > 0:  # Verifica si hay datos disponibles
            data = esp_ser.readline().decode('utf-8').strip()  # Lee y decodifica la línea
            print(f"Dato recibido de la ESP8266: {data}")

            # Validar el dato recibido
            try:
                validation = int(data)  # Convertir el dato a entero
                if validation == 0 and not event_processed:
                        print("Apagando videobeam y PC...")
                        send_videobeam_command('PWR OFF\r')  # Enviar comando al videobeam
                        time.sleep(10)  # Esperar 10 segundos antes de apagar el PC
                        shutdown_pc()  # Apagar el PC
                        event_processed = True
            except ValueError:
                print("Dato recibido no es un entero válido.")
    except Exception as e:
        print(f"Error en el bucle principal: {e}")

    time.sleep(1)  # Esperar un segundo antes de volver a leer
