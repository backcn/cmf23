#!/bin/bash

step=22 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
{
    $(curl 'http://robot.codingerror.cn/index.php?g=caiji&m=index&a=down_load_page')
    sleep $step
}
done


exit 0