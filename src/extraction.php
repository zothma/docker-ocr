<?php
    // Ce script a pour but de récupérer les informations extraites par Tesseract
    // et les transformer en données CSV.
    //
    // Le script commence par récupérer les configurations de l'utilisateur
    // concernant le type des données à extraire. Il adapte le code en fonction
    // de si l'utilisateur a précisé s'il s'agissait d'un tableau ou non.
    // Les expressions régulières sont générées en fonction, et les informations sont
    // extraites. Le fichier CSV est créé à la fin

    $config = [];
    $resultat = [];

    $entree = "etape2";
    $sortie = "etape3";
    $log_txt = "log.txt";
    $err_txt = "$sortie/erreur.txt";

    function echo_log($message) {
        global $log_txt;
        // Met en forme l'affichage d'un log
        $date = date("d/m/Y H:i:s");
        
        // Ajoute l'effet gras sur le texte demandé
        // "b(texte)" apparaîtra en gras
        $message_echo = preg_replace('/b\(([^)]+)\)/', "\e[1m" . '${1}' . "\e[0m", $message);
        echo "[#2 $date] \e[34mINFO\e[0m $message_echo\n";
        
        // On ajoute la ligne de log au fichier log
        $message_log = preg_replace('/b\(([^)]+)\)/', '${1}', $message);
        file_put_contents($log_txt, "[#2 $date] INFO $message_log\n", FILE_APPEND);
    }

    function echo_err($message) {
        global $log_txt;
        // Met en forme l'affichage d'une erreur et l'écrit à la fin
        // du fichier d'erreur
        $date = date("d/m/Y H:i:s");
        echo "[#2 $date] \e[31mERREUR\e[0m $message\n";

        file_put_contents($err_txt, "[#2 $date] ERREUR $message\n", FILE_APPEND);
    }

    function str_starts_with($ligne, $mot) {
        // Fonction existant depuis PHP 8, non implémentée en PHP 7
        // Vérifie si une ligne commence par un mot
        return boolval(preg_match("/^$mot/", $ligne));
    }

    function recuperer_config() {
        // Récupère toutes les informations du fichier de configuration
        // pour peupler l'interface $config
        global $config, $resultat, $entree;

        // Le fichier de config est différent selon si on travaille sur un texte issu d'un tableau
        // ou un texte issu d'un JSON

        $data = file("$entree/config.txt");
        $config["contenu"] = [];
        $config["format_tableau"] = false;
        $erreur = false;    // permet d'arrêter le programme après toutes les erreurs

        // détection du format s'il est donné quelque part dans le fichier
        preg_match('/tableau/im', implode("\n", $data), $matches);
        if (count($matches) > 0) {
            echo_log("Format b(tableau) donné");
            $config["format_tableau"] = true;
            // on vérifie que le nombre de colonnes est donné
            preg_match('/colonnes(?:.+)([0-9]+)/im', implode("\n", $data), $matches);
            if (count($matches) > 0) {
                echo_log("Total de b($matches[1] colonnes)");
                $config["colonnes_tableau"] = $matches[1];
            }
            else {
                echo_err("Le nombre de colonnes du tableau n'est pas indiqué dans le fichier de configuration");
                $erreur = true;
            }
        }

        // on récupère maintenant les champs. Utiliser une boucle plutot qu'une expression régulière
        // multi-ligne, comme fait avec le format, nous permet de garder l'ordre donné par l'utilisateur,
        // s'il souhaite le prénom avant le nom par exemple
        foreach ($data as $nb_ligne => $ligne) {
            foreach (["nom", "prenom", "adresse", "email", "telephone"] as $mot) {
                if (str_starts_with($ligne, $mot)) {
                    array_push($config["contenu"], $mot);

                    // on vérifie si une donnée complémentaire est passée
                    preg_match('/"(.+)"/', $ligne, $matches);
                    if (count($matches) == 0) {
                        if (in_array($mot, ["nom", "prenom", "adresse"])) {
                            // Le nom, le prénom et l'adresse ont besoin d'une donnée supplémentaire
                            $nb_ligne++;
                            echo_err("Aucune donnée supplémentaire pour le champs $mot, ou mauvaise syntaxe, ligne $nb_ligne du fichier de configuration");
                            $erreur = true;
                        }
                        elseif ($config["format_tableau"]) {
                            // Tous les champs ont besoin d'une donnée supplémentaire dans le cas d'un tableau
                            $nb_ligne++;
                            echo_err("Attention, pour le format tableau, tous les champs ont besoin d'une donnée supplémentaire");
                            echo_err("Aucune donnée supplémentaire pour le champs $mot, ou mauvaise syntaxe, ligne $nb_ligne du fichier de configuration");
                            $erreur = true;
                        }
                        else {
                            // Dans les autres cas, le champs est bon
                            echo_log("Champs b($mot) trouvé");
                            // on initialise le tableau qui contiendra toutes les valeurs
                            // extraites
                            $resultat[$mot] = [];
                        }
                    }
                    else {
                        $config["donnee_$mot"] = $matches[1];
                        echo_log("Champs b($mot) trouvé, donnée complémentaire : b($matches[1])");

                        // on initialise le tableau qui contiendra toutes les valeurs
                        // extraites
                        $resultat[$mot] = [];
                    }
                    
                }
            }
        }

        // On ne stoppe le programme qu'une fois toutes les erreurs passées
        // afin que l'utilisateur les modifie toutes plutôt que de devoir remodifier
        // à chaque fois
        if ($erreur) {
            echo_err("Arrêt, fichier de configuration erroné");
            exit(1);
        }
    }

    function gen_regex_p($prefix) {
        // Génère l'expression régulière qui permet de récupérer l'information
        // qui suit le mot donné en paramètre ("nom" : "QUINIOU" donnera, avec l'expression
        // régulière générée, QUINIOU)

        $prefix_formate = "";
        // On commence par formater le préfix : cette étape ajoute une marge d'erreur
        // dans le cas où Tesseract ne reconnaît pas bien un carcatère (un l ou un 1...)
        foreach (str_split($prefix) as $caractere) {
            // (?: ...) n'est pas un groupe récupéré par la fonction preg_match
            if (in_array($caractere, ["l", "I", "L", "1"])) {
                $prefix_formate .= "(?:l|i|1)";
            }
            elseif (in_array($caractere, ["Q", "0"])) {
                $prefix_formate .= "(?:Q|0)";
            }
            elseif (in_array($caractere, ["t", "7"])) {
                $prefix_formate .= "(?:t|7)";
            }

            // si le caractère ne pose pas de problème, on le laisse tel quel
            else {
                $prefix_formate .= $caractere;
            }
        }

        // On génère le pattern complet, qui est ensuite retourné
        // Le pattern est insensible à la casse (a = A)
        // On part du principe que le mot est entre guillemets
        $pattern = '/' . $prefix_formate . '.+"(.+)"/i';
        return $pattern;
    }

    function extraire_donnee_p($pattern, $ligne, $nom_ligne) {
        // Récupère les informations de la ligne à l'aide du pattern
        // si le nom de ligne est demandé par l'utilisateur dans le config.
        // Si la ligne correspond au pattern, l'élément extrait est ajouté au
        // tableau fourni

        global $config;
        global $resultat;

        if (in_array($nom_ligne, $config["contenu"])) {
            preg_match($pattern, $ligne, $matches);
            if (count($matches) > 0) {
                array_push($resultat[$nom_ligne], $matches[1]);
            }
        }
    }

    function main() {
        global $resultat, $config, $entree, $sortie;

        // Etape 1 : Récupération des configurations
        recuperer_config();
    
        // Etape 2 : Extraction
        echo_log("Début de l'extraction");
    
        $data = file("$entree/output.txt");
    
        if ($config["format_tableau"]) {
            // on divise le fichier en parties, chacune contenant
            // un type de donnée particulier, si l'utilisateur ne s'est
            // pas trompé en rentrant le nombre de colonnes
    
            $compteur = 0;
    
            // initialisation du tableau et des parties
            $tableau = [];
            for ($i = 0; $i < $config["colonnes_tableau"]; $i++) {
                $tableau[$i] = [];
            }
    
            foreach($data as $ligne) {
                $ligne = rtrim($ligne);
                if (boolval(preg_match("/^\w+/", $ligne))) {
                    // on ne récupère que les lignes non vides
                    if ($compteur == $config["colonnes_tableau"]) {
                        $compteur = 0;
                    }
    
                    array_push($tableau[$compteur], $ligne);
                    $compteur++;
                }
            }
    
            // On récupère les parties qui nous intéressent
            for ($i = 0; $i < $config["colonnes_tableau"]; $i++) {
                foreach ($config["contenu"] as $nom_ligne) {
                    // si l'en-tete de la partie correspond à un des champs souhaité
                    if ($config["donnee_$nom_ligne"] == $tableau[$i][0]) {
                        $resultat["$nom_ligne"] = array_slice($tableau[$i], 1); // on ne garde pas l'en-tete dans le résultat
                    }
                }
            }
        }
    
        else {
            foreach ($data as $ligne) {
                $ligne = rtrim($ligne);
        
                // NB : Les données ne sont extraites dans les fonctions
                // que si le config le demande
        
                foreach(["nom", "prenom", "adresse"] as $mot) {
                    if (in_array($mot, $config["contenu"])) {
                        $pattern = gen_regex_p($config["donnee_$mot"]);
                        extraire_donnee_p($pattern, $ligne, "$mot", $config);
                    }
                }
        
                // email
                $pattern = '/([a-z0-9][a-z.0-9]+@[a-z0-9.]+\.[a-z]{2,4})/i';
                extraire_donnee_p($pattern, $ligne, "email", $config);
        
                // téléphone
                $pattern = '/(\+?([0-9]{2,4}[-. ]?){3,4}[0-9]{2,4})/';
                extraire_donnee_p($pattern, $ligne, "telephone", $config);
            }
        }
    
    
        echo_log("Fin de l'extraction");
        $nb = count($data);
        echo_log("b($nb lignes) lues");
        $nb = count($resultat[$config["contenu"][0]]);
        echo_log("b($nb contacts) détectés");

        // Etape 3 : création du CSV
        echo_log("Création du fichier CSV");
        $csv = fopen("$sortie/output.csv", "w");
        // En-tete
        echo_log("Ajout de l'en-tête");
        fputcsv($csv, $config["contenu"], ";");
    
        echo_log("Ajout des $nb lignes");
        for ($i = 0; $i < count($resultat[$config["contenu"][0]]); $i++) {
            // Création et ajout des lignes
            $ligne_csv = [];
            foreach ($config["contenu"] as $nom_ligne) {
                array_push($ligne_csv, $resultat[$nom_ligne][$i]);
            }
    
            fputcsv($csv, $ligne_csv, ";");
        }
        echo_log("Fin, résultat dans b($sortie/output.csv)");
    }


    while (true) {
        if (file_exists("$entree/config.txt")) {
            echo_log("Fichier de configuration détecté");
            main();
            rename("$entree/config.txt", "$sortie/config.txt");
        }

        // évitons d'utiliser 100% du CPU
        sleep(1);
    }
?>
