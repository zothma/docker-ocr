#!/bin/bash

echo_log() {
    date_log=$(date +"[#3 %d/%m/%Y %H:%M:%S]")
    echo "$date_log $1" >> log.txt
    echo -e "$date_log \e[34mINFO\e[0m $1"
}

echo_err() {
    date_log=$(date +"[#3 %d/%m/%Y %H:%M:%S]")
    echo "$date_log $1" >> log.txt
    echo "$1" >> etape3/erreur.txt
    echo -e "$date_log \e[31mERREUR\e[0m $1"
}

while true
do
    if [ -f etape3/config.txt ]
    then
        echo_log "Fichier config trouvé"
        type_fic=$(egrep -io "pdf|csv|html" < etape3/config.txt)
        if [ "$type_fic" = "csv" ]
        then
            echo_log "Rien à faire le fichier est déjà en csv"
        elif [ "$type_fic" = "html" ]
        then
            echo_log "Conversion au format html"
            php modele.php > etape3/output.html
            rm etape3/output.csv
        elif [ "$type_fic" = "pdf" ]
        then
            echo_log "Conversion au format pdf"
            php modele.php > etape3/output.html
            weasyprint etape3/output.html etape3/output.pdf
            rm etape3/output.html

        else
            echo_err "Le format  demandé n'est pas un format reconnu (pdf/csv/html)"
            echo_log "Conversion au format PDF par défaut"
            php modele.php > etape3/output.html
            weasyprint etape3/output.html etape3/output.pdf
            rm etape3/output.html etape3/output.csv
        fi

        echo_log "Suppression du fichier config"
        rm etape3/config.txt
    fi

    # Évitons d'utiliser 100% du CPU
    sleep 1
done