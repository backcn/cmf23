#!/usr/bin/env bash

step=25 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
{
    sleep $step
    $(curl 'http://robot.codingerror.cn/index.php?g=caiji&m=index&a=translate_content')
}
done


exit 0