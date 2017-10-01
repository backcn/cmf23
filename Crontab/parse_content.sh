#!/bin/bash

step=5 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
{
    sleep $step
    $(curl 'http://robot.codingerror.cn/index.php?g=Caiji&m=Index&a=parse_content')
}
done


exit 0