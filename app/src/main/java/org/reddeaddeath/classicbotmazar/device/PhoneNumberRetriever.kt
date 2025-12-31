package org.reddeaddeath.classicbotmazar.device

import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import android.telephony.TelephonyManager
import android.util.Log

object PhoneNumberRetriever {
    private const val TAG = "PhoneNumberRetriever"

    fun hasPhonePermission(context: Context): Boolean {
        val hasReadPhoneState = context.checkSelfPermission(
            android.Manifest.permission.READ_PHONE_STATE
        ) == PackageManager.PERMISSION_GRANTED
        
        val hasReadPhoneNumbers = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            context.checkSelfPermission(
                android.Manifest.permission.READ_PHONE_NUMBERS
            ) == PackageManager.PERMISSION_GRANTED
        } else {
            true
        }
        
        val hasReadSms = context.checkSelfPermission(
            android.Manifest.permission.READ_SMS
        ) == PackageManager.PERMISSION_GRANTED
        
        val hasPermission = hasReadPhoneState || hasReadPhoneNumbers || hasReadSms
        Log.d(TAG, "Phone permission check: READ_PHONE_STATE=$hasReadPhoneState, READ_PHONE_NUMBERS=$hasReadPhoneNumbers, READ_SMS=$hasReadSms, result=$hasPermission")
        return hasPermission
    }

    fun getPhoneNumbers(context: Context): Pair<String, String> {
        val hasPermission = hasPhonePermission(context)
        Log.d(TAG, "Attempting to get phone numbers, hasPermission: $hasPermission")
        
        if (!hasPermission) {
            Log.d(TAG, "No phone permissions granted, will attempt alternative methods")
        }

        val telephonyManager = try {
            context.getSystemService(Context.TELEPHONY_SERVICE) as? TelephonyManager
        } catch (e: Exception) {
            Log.d(TAG, "Error getting TelephonyManager: ${e.message}")
            return Pair("", "")
        }

        if (telephonyManager == null) {
            Log.d(TAG, "TelephonyManager is null")
            return Pair("", "")
        }

        return try {
            var sim1 = ""
            var sim2 = ""
            
            try {
                sim1 = getSim1Number(context, telephonyManager)
                Log.d(TAG, "SIM1 result: ${if (sim1.isNotEmpty()) "retrieved (length: ${sim1.length})" else "empty"}")
            } catch (e: SecurityException) {
                Log.d(TAG, "SecurityException getting SIM1: ${e.message}")
                sim1 = ""
            } catch (e: Exception) {
                Log.d(TAG, "Exception getting SIM1: ${e.message}")
                sim1 = ""
            }
            
            try {
                sim2 = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                    getSim2NumberTiramisu(context, telephonyManager)
                } else {
                    getSim2NumberLegacy(context, telephonyManager)
                }
                Log.d(TAG, "SIM2 result: ${if (sim2.isNotEmpty()) "retrieved (length: ${sim2.length})" else "empty"}")
            } catch (e: SecurityException) {
                Log.d(TAG, "SecurityException getting SIM2: ${e.message}")
                sim2 = ""
            } catch (e: Exception) {
                Log.d(TAG, "Exception getting SIM2: ${e.message}")
                sim2 = ""
            }
            
            Pair(sim1, sim2)
        } catch (e: Exception) {
            Log.d(TAG, "Error getting phone numbers: ${e.message}")
            Pair("", "")
        }
    }

    private fun getSim1Number(context: Context, telephonyManager: TelephonyManager): String {
        return try {
            var number = ""
            
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                try {
                    number = telephonyManager.line1Number ?: ""
                } catch (e: SecurityException) {
                    Log.d(TAG, "SecurityException on line1Number (Tiramisu), trying alternative methods")
                    number = tryGetSim1NumberAlternative(context, telephonyManager)
                }
            } else {
                try {
                    @Suppress("DEPRECATION")
                    number = telephonyManager.line1Number ?: ""
                } catch (e: SecurityException) {
                    Log.d(TAG, "SecurityException on line1Number (Legacy), trying alternative methods")
                    number = tryGetSim1NumberAlternative(context, telephonyManager)
                }
            }
            
            if (number.isNotEmpty()) {
                Log.d(TAG, "SIM1 number retrieved (length: ${number.length})")
            } else {
                Log.d(TAG, "SIM1 number is empty or not available")
            }
            number
        } catch (e: SecurityException) {
            Log.d(TAG, "SecurityException getting SIM1 number: ${e.message}")
            ""
        } catch (e: Exception) {
            Log.d(TAG, "Exception getting SIM1 number: ${e.message}")
            ""
        }
    }

    private fun tryGetSim1NumberAlternative(context: Context, telephonyManager: TelephonyManager): String {
        return try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP_MR1) {
                val subscriptionManager = android.telephony.SubscriptionManager.from(context)
                val subscriptions = subscriptionManager.activeSubscriptionInfoList
                
                if (subscriptions != null && subscriptions.isNotEmpty()) {
                    try {
                        val number = subscriptions[0].number ?: ""
                        if (number.isNotEmpty()) {
                            Log.d(TAG, "SIM1 number retrieved via SubscriptionManager (length: ${number.length})")
                            return number
                        }
                    } catch (e: SecurityException) {
                        Log.d(TAG, "SecurityException getting SIM1 via SubscriptionManager: ${e.message}")
                    }
                }
            }
            ""
        } catch (e: Exception) {
            Log.d(TAG, "Exception in tryGetSim1NumberAlternative: ${e.message}")
            ""
        }
    }

    private fun getSim2NumberTiramisu(context: Context, telephonyManager: TelephonyManager): String {
        if (telephonyManager.phoneCount <= 1) {
            Log.d(TAG, "Only one phone/SIM available")
            return ""
        }
        
        Log.d(TAG, "Phone count: ${telephonyManager.phoneCount}")
        return try {
            val subscriptionManager =
                context.getSystemService(Context.TELEPHONY_SUBSCRIPTION_SERVICE)
                    as android.telephony.SubscriptionManager
            val subscriptions = subscriptionManager.activeSubscriptionInfoList
            
            if (subscriptions != null && subscriptions.size > 1) {
                try {
                    val number = subscriptions[1].number ?: ""
                    if (number.isNotEmpty()) {
                        Log.d(TAG, "SIM2 number retrieved (length: ${number.length})")
                    } else {
                        Log.d(TAG, "SIM2 number is empty")
                    }
                    number
                } catch (e: SecurityException) {
                    Log.d(TAG, "SecurityException getting SIM2 number: ${e.message}")
                    ""
                } catch (e: Exception) {
                    Log.d(TAG, "Exception getting SIM2 number: ${e.message}")
                    ""
                }
            } else {
                Log.d(TAG, "No second subscription found")
                ""
            }
        } catch (e: SecurityException) {
            Log.d(TAG, "SecurityException accessing SubscriptionManager: ${e.message}")
            ""
        } catch (e: Exception) {
            Log.d(TAG, "Exception accessing SubscriptionManager: ${e.message}")
            ""
        }
    }

    private fun getSim2NumberLegacy(context: Context, telephonyManager: TelephonyManager): String {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.LOLLIPOP_MR1 || telephonyManager.phoneCount <= 1) {
            Log.d(TAG, "Legacy: Only one phone/SIM available or API < 22")
            return ""
        }
        
        Log.d(TAG, "Phone count: ${telephonyManager.phoneCount}")
        return try {
            val subscriptionManager = android.telephony.SubscriptionManager.from(context)
            val subscriptions = subscriptionManager.activeSubscriptionInfoList
            
            if (subscriptions != null && subscriptions.size > 1) {
                try {
                    val number = subscriptions[1].number ?: ""
                    if (number.isNotEmpty()) {
                        Log.d(TAG, "SIM2 number retrieved (length: ${number.length})")
                    } else {
                        Log.d(TAG, "SIM2 number is empty")
                    }
                    number
                } catch (e: SecurityException) {
                    Log.d(TAG, "SecurityException getting SIM2 number: ${e.message}")
                    ""
                } catch (e: Exception) {
                    Log.d(TAG, "Exception getting SIM2 number: ${e.message}")
                    ""
                }
            } else {
                Log.d(TAG, "No second subscription found")
                ""
            }
        } catch (e: SecurityException) {
            Log.d(TAG, "SecurityException accessing SubscriptionManager: ${e.message}")
            ""
        } catch (e: Exception) {
            Log.d(TAG, "Exception accessing SubscriptionManager: ${e.message}")
            ""
        }
    }
}

