#include <SPI.h>
#include <WiFiNINA.h>
#include <ArduinoHttpClient.h>
#include "secret.h"

//please enter your sensitive data in the Secret tab
char ssid[] = SECRET_SSID;                // your network SSID (name)
char pass[] = SECRET_PASS;                // your network password (use for WPA, or use as key for WEP)
int status = WL_IDLE_STATUS;              // the Wi-Fi radio's status
int ledState = LOW;                       //ledState used to set the LED
unsigned long previousMillisInfo = 0;     //will store last time Wi-Fi information was updated
unsigned long previousMillisLED = 0;      // will store the last time LED was updated
const int intervalInfo = 5000;            // interval at which to update the board information

//Http Connection to Remote Congif
char hueHubIP[] = SECRET_HOST;
WiFiClient wifi;
HttpClient httpClient = HttpClient(wifi, hueHubIP);

void setup() {
  //Initialize serial and wait for port to open:
  Serial.begin(9600);
  while (!Serial);

  // set the LED as output
  pinMode(LED_BUILTIN, OUTPUT);

  // attempt to connect to Wi-Fi network:
  while (status != WL_CONNECTED) {
    Serial.print("Attempting to connect to network: ");
    Serial.println(ssid);
    // Connect to WPA/WPA2 network:
    status = WiFi.begin(ssid, pass);

    // wait 10 seconds for connection:
    delay(10000);
  }

  // you're connected now, so print out the data:
  outputWifiConnection();
}

void loop() {
  delay(10000);
  wifiConnectionHealthCheck();
  sendHttpRequest();
}


void getMockVisibleImage() {

}

void getMockNearInfraredImage() {

}

void sendHttpRequest() {
  String size_rgb = "123";
  String size_nir = "456";


  String transferInitRequest = SECRET_PATH_TRANSFER_INIT;
  String contentTypeInitRequest = "application/json";
  String jsonPayloadInitRequest = "{\"size_rgb\": " + size_rgb + ",";
  jsonPayloadInitRequest += "\"size_nir\": " + size_nir + "}";

  httpClient.put(transferInitRequest, contentTypeInitRequest, jsonPayloadInitRequest);
  int statusCode = httpClient.responseStatusCode();
  String response = httpClient.responseBody();

  Serial.print(transferInitRequest);
  Serial.print(" ");
  Serial.print(jsonPayloadInitRequest);
  Serial.print("Status code from server: ");
  Serial.println(statusCode);


  int labelStart = response.indexOf("id\":");
  String uuid = response.substring(labelStart+5, labelStart+28);
  Serial.print("Server response: ");
  Serial.println(uuid);
  Serial.println();
}

void wifiConnectionHealthCheck() {
 unsigned long currentMillisInfo = millis();

  // check if the time after the last update is bigger the interval
  if (currentMillisInfo - previousMillisInfo >= intervalInfo) {
    previousMillisInfo = currentMillisInfo;

    Serial.println("Board Information:");
    // print your board's IP address:
    IPAddress ip = WiFi.localIP();
    Serial.print("IP Address: ");
    Serial.println(ip);

    // print your network's SSID:
    Serial.println();
    Serial.println("Network Information:");
    Serial.print("SSID: ");
    Serial.println(WiFi.SSID());

    // print the received signal strength:
    long rssi = WiFi.RSSI();
    Serial.print("signal strength (RSSI):");
    Serial.println(rssi);
    Serial.println("---------------------------------------");
  }

  unsigned long currentMillisLED = millis();
  
  // measure the signal strength and convert it into a time interval
  int intervalLED = WiFi.RSSI() * -10;
 
  // check if the time after the last blink is bigger the interval 
  if (currentMillisLED - previousMillisLED >= intervalLED) {
    previousMillisLED = currentMillisLED;

    // if the LED is off turn it on and vice-versa:
    if (ledState == LOW) {
      ledState = HIGH;
    } else {
      ledState = LOW;
    }

    // set the LED with the ledState of the variable:
    digitalWrite(LED_BUILTIN, ledState);
  }
}

void outputWifiConnection() {
  Serial.println("You're connected to the network");
  Serial.println("---------------------------------------");
    for (int i = 0; i <= 5; i++) {
        digitalWrite(LED_BUILTIN, HIGH);  // turn the LED on (HIGH is the voltage level)
        delay(500);                      // wait for a second
        digitalWrite(LED_BUILTIN, LOW);   // turn the LED off by making the voltage LOW
        delay(500); ;
    }
}