<?php

class MessageStatuses
{
    const WAITING_FOR_FIRST_SENDING = 0;
    const WAITING_FOR_REPEAT_SENDING = 1;
    const WAITING_FOR_STATUS_UPDATE = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_UNDELIVERED = 4;
}