package org.reddeaddeath.classicbotmazar.network

import android.util.Log
import java.net.URL

object UrlValidator {
    private const val TAG = "UrlValidator"

    fun isValidUrl(urlString: String): Boolean {
        return try {
            val url = URL(urlString)
            val isValid = url.protocol == "http" || url.protocol == "https"
            Log.d(TAG, "URL validation: $urlString -> $isValid")
            isValid
        } catch (e: Exception) {
            Log.d(TAG, "URL validation failed: $urlString - ${e.message}")
            false
        }
    }
}

