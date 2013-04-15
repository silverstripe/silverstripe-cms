<?php

/**
 * For load-balanced instances, returning status other than 200 from this script
 * will cause the load balancer to fail over to the hosts it considers healthy.
 *
 * This file is polled every 10s.
 */

echo "OK";
