#!/bin/bash
echo_log() {
    date_log=$(date +"[#1 %d/%m/%Y %H:%M:%S]")
    echo "$date_log $1" >> log.txt
    echo -e "$date_log \e[34mINFO\e[0m $1"
}

while true
do 
    if [ -f etape1/config.txt ]
    then 
        type=$(egrep -i "tableau" < etape1/config.txt)
        rm etape[23]/*
        echo $type

        if [ "$type" = "tableau" ]
        then
            echo_log "Trouvé config, type: tableau"
            tesseract etape1/*.png etape2/output --psm 12
            mv "etape1/config.txt" "etape2/"
        else
            echo_log "Trouvé config, type: normal"
            tesseract etape1/*.png etape2/output
            mv "etape1/config.txt" "etape2/"
        fi

        rm etape1/*
    fi

    # Évitons d'utiliser 100% du CPU
    sleep 1
done