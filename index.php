<?php
/**
 * Ma page d’indexation de mes instances Dolibarr locales.
 */

// pour js et transition css : délai à compter de la dernière frappe clavier avant lequel on ne lance pas la recherche
$searchDelay = 400;
// pour js : délai à compter de la recherche effective après lequel le contenu de la barre de recherche est
// sélectionné (pour pouvoir faire une nouvelle recherche plus rapidement)
$selectDelay = 1000;
// pour js : délai pour redonner le focus à la barre de recherche lorsqu'elle le perd
$focusDelay = 300;
?>

<html>
<head>
    <meta charset="utf-8">
    <title>Dolibarr Clients</title>
    <style>
        html {
            background-color: black;
        }
        body {
            background-color: white;
            width: 50%;
            margin: auto;
            margin-top: 2vw;
            padding: 2% 2%;
        }
        li.client {
            display: inline-block;
            margin: 1%;
            width: 12em;
            height: 1.7em;
            border: solid 1px;
            padding: 9px 2px 2px 2px;
            border-radius: 3px;
            background-color: #f5f1f6;
            font-family: sans;
            text-align: center;
        }
        a:link, a:hover, a:visited {
            text-decoration: none;
        }
        li.client.hidden {
            display: none;
        }
        li.client.highlighted {
            background-color: #fffdb8;
            border: solid 1px red;
        }
        #progress {
            display: inline-block;
            width: 0;
            min-width: 0;
            border-top: solid 2px #726565;
            transition-property: width,border-top-color,border-spacing;
            transition-duration: <?php echo $searchDelay; ?>ms;
            transition-timing-function: linear;
        }
        #progress.started {
            width: 70%;
            border-top-color: white;
        }
        #find-client {
            width: 70%;
            min-width: 300px;
        }
    </style>
</head>
<body>
<h1 style="font-family: arial; text-align: center;">Clients dolibarr</h1>
    <div id="clients-dolibarr">
        <div id="find-with-progress" style="text-align: center">
            <input id="find-client" style="font-size: 250%" /><br/>
            <div id="progress"></div>
        </div>
        <ul>
            <?php
            $dir_handle = opendir('.');
            $dir_list = [];
            // selon la structure, ça peut-être "%s/htdocs/" ou "%s/dolibarr/htdocs/"
            $client_template = '<li class="client"><a href="%s">%s</a></li>';
    
            while ($file = readdir($dir_handle)) {
                if (!is_dir($file) || $file === '.' || $file === '..') {
                    continue;
                }
                $url_base_dir = $file . '/htdocs';
                if (!is_dir($url_base_dir)) {
                    $url_base_dir = $file . '/dolibarr/htdocs';
                }
                if (!is_dir($url_base_dir)) {
                    continue;
                }
    
                $dir_list[] = sprintf($client_template, $url_base_dir, ucfirst($file));
            }
    
            sort($dir_list);
    
            foreach ($dir_list as $client_entry) {
                echo($client_entry);
            }
    
            // mon virtualhost générique
            echo(sprintf($client_template, 'http://local-dolibarr', '[generic] local-dolibarr'));
            ?>
        </ul>
    </div>
</body>
</html>


<script type="application/javascript">
    /**
     * Ce script sert simplement à pouvoir filtrer les instances grâce à la barre de
     * recherche. Le filtrage se fait simplement sur le nom du lien.
     *
     * 
     */
    window.addEventListener('load', () => {
        let searchDelay = <?php echo $searchDelay; ?>;
        let progress = document.querySelector('#progress');
        let findWithProgress = document.querySelector('#find-with-progress');
        let filterClients = function (search) {
            let clients = document.querySelectorAll('li.client');
            let s;
            try { s = new RegExp(search, 'i'); }
            catch {
                // on échappe tous les caractères qui ont une interprétation en regexp
                s = new RegExp(search.replace(/([\\(){}+*?\[\]|])/, '\\$1'));
            }
            for (let i = 0; i < clients.length; i++) {
                let client = clients[i];
                if (search === '') {
                    client.classList.remove('hidden');
                    client.classList.remove('highlighted');
                    continue;
                }
                if (client.innerText.search(s) !== -1) {
                    client.classList.remove('hidden');
                    client.classList.add('highlighted');
                } else {
                    client.classList.remove('highlighted');
                    client.classList.add('hidden');
                }
            }
        };

        /**
         * Fonction plus gadget qu'autre chose : permet de visualiser le temps d'attente entre une frappe de clavier
         * et l'appel de la fonction de recherche.
         *
         * La fonction réinitialise la barre de progression animée en CSS avec le même délai que delayFilterClients.
         */
        let resetProgressBar = function () {
            progress.remove();
            progress = document.createElement('div');
            progress.id = 'progress';
            findWithProgress.appendChild(progress);
            void progress.offsetWidth; // force le navigateur à rafraîchir l'état du DOM (https://medium.com/better-programming/how-to-restart-a-css-animation-with-javascript-and-what-is-the-dom-reflow-a86e8b6df00f)
            progress.classList.add('started');
        }
        /**
         * Fonction appelée à chaque frappe de clavier dans la barre de recherche.
         *
         * Ne lance la recherche que si quelques dixièmes de secondes se sont écoulés depuis le
         * dernier appel (ça permet de ne pas lancer la recherche effective à chaque frappe de
         * clavier, mais seulement lorsque le clavier est resté "au repos" un petit moment).
         *
         * Si le clavier reste au repos plus longtemps (1 seconde), le contenu de l'input est
         * sélectionné pour ne pas avoir à faire Ctrl+A si on veut faire une nouvelle recherche.
         *
         * @param search Le texte recherché
         */
        let delayFilterClients = function (searchFunction, search) {
            let caller = delayFilterClients;
            // on supprime les timeouts
            caller.searchTO && clearTimeout(caller.searchTO);
            caller.selectTO && clearTimeout(caller.selectTO);
            resetProgressBar();
            caller.searchTO = setTimeout(() => {
                searchFunction(search);
                caller.selectTO = setTimeout(() => {
                    inpFindClient.select();
                }, <?php echo $selectDelay; ?>);
            }, searchDelay);
        };
        let inpFindClient = document.querySelector('#find-client');
        inpFindClient.focus();
        inpFindClient.select();
        inpFindClient.addEventListener('keyup', (ev) => {
            if (ev.keyCode === 27) inpFindClient.select(); // Echap = sélectionner tout
            delayFilterClients(filterClients, inpFindClient.value);
        });
        inpFindClient.addEventListener('blur', (ev) => {
            // si ev.relatedTarget est défini, c'est probablement qu'on a cliqué sur un élément ou qu'on veut
            // naviguer au clavier (Tab)
            if (!ev.relatedTarget) setTimeout(() => inpFindClient.focus(), <?php echo $focusDelay; ?>);
        });
    });
</script>
