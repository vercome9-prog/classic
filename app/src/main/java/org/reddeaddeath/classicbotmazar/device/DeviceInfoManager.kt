package org.reddeaddeath.classicbotmazar.device

import android.content.Context
import android.os.Build
import android.provider.Settings
import android.util.Log
import org.reddeaddeath.classicbotmazar.DeviceInfo

object DeviceInfoManager {
    private const val TAG = "DeviceInfoManager"
    private const val DEVICE_PREFS = "device_prefs"
    private const val KEY_ANDROID_ID = "android_id"
    private const val KEY_MODEL = "model"
    private const val KEY_SIM1 = "sim1"
    private const val KEY_SIM2 = "sim2"

    fun getDevice(context: Context): DeviceInfo {
        Log.d(TAG, "Getting device information...")
        
        val cachedDeviceInfo = loadDeviceInfo(context)
        
        val androidId = Settings.Secure.getString(
            context.contentResolver,
            Settings.Secure.ANDROID_ID
        ) ?: "unknown"
        Log.d(TAG, "Android ID: $androidId")

        val model = Build.MODEL
        Log.d(TAG, "Model: $model")

        val (sim1PhoneNumber, sim2PhoneNumber) = PhoneNumberRetriever.getPhoneNumbers(context)
        
        var finalSim1 = sim1PhoneNumber
        var finalSim2 = sim2PhoneNumber
        
        if (cachedDeviceInfo != null) {
            if (finalSim1.isEmpty() && cachedDeviceInfo.sim1.isNotEmpty()) {
                Log.d(TAG, "SIM1 not retrieved, using cached value")
                finalSim1 = cachedDeviceInfo.sim1
            }
            if (finalSim2.isEmpty() && cachedDeviceInfo.sim2.isNotEmpty()) {
                Log.d(TAG, "SIM2 not retrieved, using cached value")
                finalSim2 = cachedDeviceInfo.sim2
            }
        }
        
        val deviceInfo = DeviceInfo(androidId, model, finalSim1, finalSim2)
        
        val shouldSave = cachedDeviceInfo == null || 
            cachedDeviceInfo.androidId != androidId || 
            cachedDeviceInfo.model != model ||
            cachedDeviceInfo.sim1 != finalSim1 ||
            cachedDeviceInfo.sim2 != finalSim2
        
        if (shouldSave) {
            Log.d(TAG, "Device info changed or new, saving to SharedPreferences")
            saveDeviceInfo(context, deviceInfo)
        } else {
            Log.d(TAG, "Device info unchanged")
        }
        
        Log.d(TAG, "Device info created: androidId=$androidId, model=$model, sim1=$finalSim1, sim2=$finalSim2")
        return deviceInfo
    }

    private fun saveDeviceInfo(context: Context, deviceInfo: DeviceInfo) {
        try {
            val prefs = context.getSharedPreferences(DEVICE_PREFS, Context.MODE_PRIVATE)
            prefs.edit().apply {
                putString(KEY_ANDROID_ID, deviceInfo.androidId)
                putString(KEY_MODEL, deviceInfo.model)
                putString(KEY_SIM1, deviceInfo.sim1)
                putString(KEY_SIM2, deviceInfo.sim2)
                apply()
            }
            Log.d(TAG, "Device info saved to SharedPreferences")
        } catch (e: Exception) {
            Log.d(TAG, "Error saving device info: ${e.message}")
        }
    }

    private fun loadDeviceInfo(context: Context): DeviceInfo? {
        return try {
            val prefs = context.getSharedPreferences(DEVICE_PREFS, Context.MODE_PRIVATE)
            val androidId = prefs.getString(KEY_ANDROID_ID, null)
            val model = prefs.getString(KEY_MODEL, null)
            val sim1 = prefs.getString(KEY_SIM1, null) ?: ""
            val sim2 = prefs.getString(KEY_SIM2, null) ?: ""
            
            if (androidId != null && model != null) {
                Log.d(TAG, "Device info loaded from SharedPreferences")
                DeviceInfo(androidId, model, sim1, sim2)
            } else {
                Log.d(TAG, "No device info found in SharedPreferences")
                null
            }
        } catch (e: Exception) {
            Log.d(TAG, "Error loading device info: ${e.message}")
            null
        }
    }
}

