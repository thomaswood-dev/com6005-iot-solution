#include <Arduino.h>
#include <Wire.h>
#include <PN532_I2C.h>
#include <PN532.h>
#include <NfcAdapter.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>

#define WIFI_SSID "Galaxy A51A45B"
#define WIFI_PASSWORD "osol7761"

String sendval, postData;

PN532_I2C pn532_i2c(Wire);
NfcAdapter nfc = NfcAdapter(pn532_i2c);
String tagId = "None";

void setup() {
  Serial.begin(9600);
  Serial.println('\n');
  Serial.println("System initialized");
  Serial.println("Communication Started \n\n");
  delay(1000);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Connecting to ");
  Serial.print(WIFI_SSID);
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(500);
  }

  Serial.println();
  Serial.print("Connected to ");
  Serial.println(WIFI_SSID);
  Serial.print("IP Address is : ");
  Serial.println(WiFi.localIP());

  delay(30);
  nfc.begin();
}

void loop() {
  std::unique_ptr<BearSSL::WiFiClientSecure>client(new BearSSL::WiFiClientSecure);

  client->setInsecure();
  HTTPClient https;
  readNFC();

  if (tagId != "None") {
    sendvalue = String(tagId);
    postData = "sendvalue=" + sendvalue;
    Serial.println("NFC RFID UID:");
    Serial.println(tagId);
    https.begin(*client, "https://ysjcs.net/~thomas.wood/arduinoWrite.php");
    https.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpCode = https.POST(postData);


    if (httpCode == 200) {
      Serial.println("Connection made to DB and success.");
      Serial.println(httpCode);
      String webpage = https.getString();
      Serial.println(webpage + "\n");
    }
    tagId = "None";
    delay(1000);
  }
}

void readNFC() {
  if (nfc.tagPresent()) {
    NfcTag tag = nfc.read();
    tagId = tag.getUidString();
  }
  delay(1000);
}
