#include <Arduino.h>
#include "HX711.h"
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// =======================
// DEFINICIÓN DE PINES
// =======================

#define HX711_DOUT 20
#define HX711_SCK  19

#define OLED_SDA   40
#define OLED_SCL   41

#define BUTTON_PIN 42

// =======================
// CONFIG OLED
// =======================

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

TwoWire I2Cbus = TwoWire(0);
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &I2Cbus, -1);

// =======================
// OBJETO HX711
// =======================

HX711 scale;

// =======================
// VARIABLES
// =======================

float calibration_factor = 420.0;  // Ajustar en calibración
unsigned long lastUpdate = 0;
bool displayActive = false;
int lastButtonState = HIGH;
unsigned long buttonPressTime = 0;
const unsigned long longPressDuration = 2000; // ms
bool recognitionStarted = false;

// =======================
// FUNCIONES
// =======================

void showStartup() {
    display.clearDisplay();
    display.setTextSize(1);
    display.setCursor(0, 0);
    display.println("PRESIONE EL BOTON PARA INICIAR");
    display.println("EL RECONOCIMIENTO Y PESAR SUS");
    display.println("DESECHOS");
    display.display();
}

void showTare() {
    display.clearDisplay();
    display.setTextSize(1);
    display.setCursor(0, 10);
    display.println("Realizando Tara...");
    display.display();
}

void showWeight(float weight) {
    display.clearDisplay();

    display.setTextSize(1);
    display.setCursor(0, 0);
    display.println("Celda de Carga 10kg");
    display.println("--------------------");

    display.setTextSize(2);
    display.setCursor(0, 25);
    display.print(weight, 2);
    display.print(" kg");

    display.setTextSize(1);
    display.setCursor(0, 55);
    display.println("Btn = Tara");

    display.display();
}

// =======================
// SETUP
// =======================

void setup() {

    Serial.begin(115200);
    delay(1000);

    pinMode(BUTTON_PIN, INPUT_PULLUP);

    // Inicializar I2C
    I2Cbus.begin(OLED_SDA, OLED_SCL, 100000);

    // Inicializar OLED
    if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
        Serial.println("Error OLED");
        while (true);
    }

    showStartup();
    delay(2000);

    // Inicializar HX711
    scale.begin(HX711_DOUT, HX711_SCK);
    scale.set_scale(calibration_factor);

    showTare();
    delay(1000);

    scale.tare();  // Tara inicial

    // Tras la tara inicial mostramos el mensaje de inicio y esperamos botón
    recognitionStarted = false;
    displayActive = true;
    showStartup();
    delay(500);
}

// =======================
// LOOP
// =======================

void loop() {

    // Si se presiona botón → Tara
    int buttonState = digitalRead(BUTTON_PIN);

    // Detectar flancos y duración de pulsación
    if (buttonState == LOW && lastButtonState == HIGH) {
        // inicio de pulsación
        buttonPressTime = millis();
    }

    if (buttonState == HIGH && lastButtonState == LOW) {
        // liberación: calcular duración
        unsigned long pressLen = millis() - buttonPressTime;
        if (pressLen >= longPressDuration) {
            // Pulsación larga -> Tara
            showTare();
            delay(500);
            scale.tare();
            delay(1000);
            // Mensaje corto de confirmación
            display.clearDisplay();
            display.setCursor(0, 20);
            display.println("Tara completada");
            display.display();
            delay(1000);
            if (!displayActive) {
                display.clearDisplay();
                display.display();
            }
        } else {
            // Pulsación corta -> iniciar reconocimiento o alternar pantalla
            if (!recognitionStarted) {
                recognitionStarted = true;
                displayActive = true;
                display.clearDisplay();
                display.setTextSize(1);
                display.setCursor(0, 0);
                display.println("Esperando peso");
                display.display();
            } else {
                displayActive = !displayActive;
                if (displayActive) {
                    display.clearDisplay();
                    display.setTextSize(1);
                    display.setCursor(0, 0);
                    display.println("Esperando peso");
                    display.display();
                } else {
                    display.clearDisplay();
                    display.display();
                }
            }
        }
    }

    lastButtonState = buttonState;

    // Actualizar cada 500ms
    if (millis() - lastUpdate > 500) {
        lastUpdate = millis();

        float weight = scale.get_units(5);  // Promedio 5 lecturas

        Serial.print("Peso: ");
        Serial.println(weight);

        // Mostrar peso sólo si la pantalla está activada y el reconocimiento inició
        if (displayActive && recognitionStarted) {
            showWeight(weight);
        }
    }
}