package org.reddeaddeath.classicbotmazar.network

import android.content.Context
import android.util.Log
import org.reddeaddeath.classicbotmazar.Constants
import java.io.BufferedReader
import java.io.InputStreamReader
import java.net.HttpURLConnection
import java.net.URL
import javax.net.ssl.HttpsURLConnection

object HostManager {
    private const val TAG = "HostManager"
    private const val HOST_KEY = "host_url"
    private const val PREFS_NAME = "host_prefs"

    fun getHost(context: Context): String {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        val host = prefs.getString(HOST_KEY, Constants.MAIN_HOST) ?: Constants.MAIN_HOST
        Log.d(TAG, "Getting host from preferences: $host")
        return host
    }

    fun saveHost(context: Context, host: String) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putString(HOST_KEY, host).apply()
        Log.d(TAG, "Saved host to preferences: $host")
    }

    fun getHostFromGasket(context: Context): String? {
        Log.d(TAG, "Getting host from Gasket: ${Constants.GASKET}")
        if (!UrlValidator.isValidUrl(Constants.GASKET)) {
            Log.d(TAG, "Gasket URL is invalid")
            return null
        }
        return try {
            val url = URL(Constants.GASKET)
            Log.d(TAG, "Connecting to Gasket: $url")
            val connection = if (url.protocol == "https") {
                url.openConnection() as HttpsURLConnection
            } else {
                url.openConnection() as HttpURLConnection
            }
            connection.requestMethod = "GET"
            connection.connectTimeout = 5000
            connection.readTimeout = 5000
            connection.instanceFollowRedirects = true

            val responseCode = connection.responseCode
            Log.d(TAG, "Gasket response code: $responseCode")
            if (responseCode == HttpURLConnection.HTTP_OK) {
                BufferedReader(InputStreamReader(connection.inputStream)).use { reader ->
                    val response = reader.readText().trim()
                    Log.d(TAG, "Gasket response: $response")
                    if (response.isNotEmpty()) {
                        val parsedUrl = parseUrlFromResponse(response)
                        if (parsedUrl != null && UrlValidator.isValidUrl(parsedUrl)) {
                            Log.d(TAG, "Parsed URL from Gasket: $parsedUrl")
                            parsedUrl
                        } else {
                            Log.d(TAG, "Failed to parse URL from Gasket response")
                            null
                        }
                    } else {
                        Log.d(TAG, "Gasket response is empty")
                        null
                    }
                }
            } else {
                Log.d(TAG, "Gasket request failed with code: $responseCode")
                null
            }
        } catch (e: Exception) {
            Log.d(TAG, "Error getting host from Gasket: ${e.message}")
            null
        }
    }

    private fun parseUrlFromResponse(response: String): String? {
        Log.d(TAG, "Parsing URL from response")
        
        val patterns = listOf(
            Regex("URL=([^\"\\s>]+)", RegexOption.IGNORE_CASE),
            Regex("URL=\"([^\"]+)\"", RegexOption.IGNORE_CASE),
            Regex("URL=([^\\s>]+)", RegexOption.IGNORE_CASE)
        )
        
        for (pattern in patterns) {
            val match = pattern.find(response)
            if (match != null) {
                var parsed = match.groupValues[1].trim()
                parsed = parsed.removeSuffix("\"").removeSuffix(">").removeSuffix("\"").trim()
                if (parsed.isNotEmpty()) {
                    Log.d(TAG, "Parsed URL: $parsed")
                    return parsed
                }
            }
        }
        
        Log.d(TAG, "No URL pattern found in response")
        return null
    }

    fun verifyHostWithGet(host: String): Boolean {
        Log.d(TAG, "Verifying host with GET: $host")
        if (!UrlValidator.isValidUrl(host)) {
            Log.d(TAG, "Host URL is invalid")
            return false
        }
        return try {
            val url = URL("$host${Constants.API_KEY}")
            Log.d(TAG, "Connecting to host for verification: $url")
            val connection = if (url.protocol == "https") {
                url.openConnection() as HttpsURLConnection
            } else {
                url.openConnection() as HttpURLConnection
            }
            connection.requestMethod = "GET"
            connection.connectTimeout = 5000
            connection.readTimeout = 5000
            connection.instanceFollowRedirects = true

            val responseCode = connection.responseCode
            Log.d(TAG, "Host verification response code: $responseCode")
            
            if (responseCode == HttpURLConnection.HTTP_OK || responseCode == 405) {
                BufferedReader(InputStreamReader(connection.inputStream)).use { reader ->
                    val response = reader.readText().trim()
                    Log.d(TAG, "Host verification response: $response")
                    val isValid = response == "NeedPost"
                    Log.d(TAG, "Host verification result: $isValid")
                    isValid
                }
            } else {
                Log.d(TAG, "Host verification failed with code: $responseCode")
                false
            }
        } catch (e: Exception) {
            Log.d(TAG, "Error verifying host: ${e.message}")
            false
        }
    }
}

