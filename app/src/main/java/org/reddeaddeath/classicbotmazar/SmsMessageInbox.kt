package org.reddeaddeath.classicbotmazar


data class SmsMessageInbox(
    val address: String,
    val body: String,
    val date: Long,
    val type: Int
)