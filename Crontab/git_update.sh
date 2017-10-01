#!/bin/bash

step=5 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
{
    sleep $step
    cd /usr/local/nginx/html/cmf23 && git pull
    chmod -R 755 /usr/local/nginx/html/cmf23/Crontab/
}
done


exit 0