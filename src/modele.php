<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Document SAE</title>

    <?php
        $pourcentage = "90%";
        $lignes = file("etape3/config.txt");
        if (boolval(preg_match('/format(.+)"pdf"/im', implode("\n", $lignes)))){
            $pourcentage = "100%";
        }
    ?>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            color: #212529;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px !important;
        }

        table {
            margin: auto;
            width: <?php echo $pourcentage; ?> !important;
            border-collapse: collapse;
            border: 1px solid #dee2e6;
        }

        td, th {
            padding: .75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, .05)
        }
    </style>
</head>
<body>
    <h1>Liste des contacts</h1>
    <section>
        <table>
            <?php
                // En-tête
                echo "<tr>\n";
                $lines = file('etape3/output.csv');
                foreach(explode(';', $lines[0]) as $entete) {
                    $entete = ucfirst($entete);
                    echo "\t<th>$entete</th>\n";
                }
                echo "</tr>\n";

                // Corps du tableau
                foreach(array_slice($lines, 1) as $l) {
                    echo "<tr>\n";
                    foreach(explode(';', $l) as $data) {
                        echo "\t<td>$data</td>\n";
                    }
                    echo "</tr>\n";
                }
            ?>
        </table>
    </section>    
</body>
</html>

