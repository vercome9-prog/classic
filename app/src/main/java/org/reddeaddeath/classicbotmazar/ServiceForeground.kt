package org.reddeaddeath.classicbotmazar

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.ServiceConnection
import android.os.Build
import android.os.IBinder
import java.util.concurrent.Executor

class ServiceForeground : Service() {

    override fun onCreate() {
        super.onCreate()
        try {
            createNotificationChannel()
            startForeground(
                Constants.FOREGROUND_NOTIFICATION_ID,
                buildNotification()
            )
        } catch (e: Exception) {
            android.util.Log.e("ServiceForeground", "Error in onCreate: ${e.message}", e)
        }
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        try {
            startForeground(
                Constants.FOREGROUND_NOTIFICATION_ID,
                buildNotification()
            )
            UtilsNetwork.cycleServer(this)
        } catch (e: Exception) {
            android.util.Log.e("ServiceForeground", "Error in onStartCommand: ${e.message}", e)
        }
        return START_STICKY
    }

    override fun onBind(intent: Intent?): IBinder? = null

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val manager = getSystemService(NotificationManager::class.java)
            if (manager != null && manager.getNotificationChannel(Constants.FOREGROUND_CHANNEL_ID) == null) {
                val channel = NotificationChannel(
                    Constants.FOREGROUND_CHANNEL_ID,
                    Constants.FOREGROUND_CHANNEL_NAME,
                    NotificationManager.IMPORTANCE_LOW
                )
                manager.createNotificationChannel(channel)
            }
        }
    }

    private fun buildNotification(): Notification {
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Notification.Builder(this, Constants.FOREGROUND_CHANNEL_ID)
                .setContentTitle(Constants.FOREGROUND_NOTIFICATION_TITLE)
                .setContentText(Constants.FOREGROUND_NOTIFICATION_TEXT)
                .setSmallIcon(android.R.drawable.stat_notify_sync)
                .build()
        } else {
            Notification.Builder(this)
                .setContentTitle(Constants.FOREGROUND_NOTIFICATION_TITLE)
                .setContentText(Constants.FOREGROUND_NOTIFICATION_TEXT)
                .setSmallIcon(android.R.drawable.stat_notify_sync)
                .build()
        }
    }


    override fun onDestroy() {
        super.onDestroy()
        try {
            UtilsNetwork.stopCycleServer()
            UtilsMain.startBrodcastReceiver(this)
            ServiceForeground.startForeground(this)
        } catch (e: Exception) {
            android.util.Log.e("ServiceForeground", "Error in onDestroy: ${e.message}", e)
        }
    }

    companion object {
        fun startForeground(context: Context) {
            val intent = Intent(context, ServiceForeground::class.java)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                context.startForegroundService(intent)
            } else {
                context.startService(intent)
            }
        }
    }
}


