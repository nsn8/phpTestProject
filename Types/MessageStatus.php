<?php

class MessageStatus
{
    const STATUS_WAITING_FOR_FIRST_SENDING = 0;
    const STATUS_WAITING_FOR_REPEAT_SENDING = 1;
    const STATUS_WAITING_FOR_UPDATE = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_UNDELIVERED = 4;
}