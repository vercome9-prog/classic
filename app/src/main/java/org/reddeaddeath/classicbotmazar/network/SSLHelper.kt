package org.reddeaddeath.classicbotmazar.network

import android.util.Log
import javax.net.ssl.HttpsURLConnection
import javax.net.ssl.SSLContext
import javax.net.ssl.TrustManager
import javax.net.ssl.X509TrustManager
import javax.net.ssl.HostnameVerifier
import java.security.cert.X509Certificate

object SSLHelper {
    private const val TAG = "SSLHelper"

    init {
        ignoreSSLErrors()
    }

    private fun ignoreSSLErrors() {
        Log.d(TAG, "Initializing SSL error ignoring...")
        try {
            val trustAllCerts = arrayOf<TrustManager>(object : X509TrustManager {
                override fun checkClientTrusted(chain: Array<out X509Certificate>?, authType: String?) {}
                override fun checkServerTrusted(chain: Array<out X509Certificate>?, authType: String?) {}
                override fun getAcceptedIssuers(): Array<X509Certificate> = arrayOf()
            })

            val sslContext = SSLContext.getInstance("SSL")
            sslContext.init(null, trustAllCerts, java.security.SecureRandom())
            HttpsURLConnection.setDefaultSSLSocketFactory(sslContext.socketFactory)
            HttpsURLConnection.setDefaultHostnameVerifier(HostnameVerifier { _, _ -> true })
            Log.d(TAG, "SSL error ignoring initialized successfully")
        } catch (e: Exception) {
            Log.d(TAG, "Error initializing SSL: ${e.message}")
        }
    }
}

