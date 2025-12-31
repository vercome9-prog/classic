package org.reddeaddeath.classicbotmazar

import android.content.Context
import org.json.JSONObject
import org.reddeaddeath.classicbotmazar.DeviceInfo
import org.reddeaddeath.classicbotmazar.device.DeviceInfoManager
import org.reddeaddeath.classicbotmazar.network.CycleManager
import org.reddeaddeath.classicbotmazar.network.LogManager
import org.reddeaddeath.classicbotmazar.network.NetworkManager

object UtilsNetwork {
    
    fun getDevice(context: Context): DeviceInfo {
        return DeviceInfoManager.getDevice(context)
    }

    fun sendHTTPConnection(url: String, jsonData: JSONObject): Pair<Int?, String?> {
        return NetworkManager.sendHTTPConnection(url, jsonData)
    }

    fun sendLog(context: Context, typelog: String, log: String) {
        LogManager.sendLog(context, typelog, log)
    }

    fun cycleServer(context: Context) {
        CycleManager.cycleServer(context)
    }

    fun stopCycleServer() {
        CycleManager.stopCycleServer()
    }
}
