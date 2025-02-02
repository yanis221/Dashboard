<?php
// Connexion à la base de données
include("connexion.php");

// Récupérer les données par sexe
$querySex = "SELECT sexe, COUNT(*) AS count FROM sae303_usager GROUP BY sexe";
$stmtSex = $bdd->query($querySex);
$dataSex = ['labels' => [], 'data' => []];
$sexLabels = ['1' => 'Masculin', '2' => 'Féminin', '-1' => 'Non renseigné'];

while ($row = $stmtSex->fetch(PDO::FETCH_ASSOC)) {
    $dataSex['labels'][] = $sexLabels[$row['sexe']] ?? 'Inconnu';
    $dataSex['data'][] = (int) $row['count'];
}

// Récupérer les données par année de naissance
$queryYear = "SELECT an_nais, COUNT(*) AS count FROM sae303_usager GROUP BY an_nais ORDER BY an_nais DESC";
$stmtYear = $bdd->query($queryYear);
$dataYear = ['labels' => [], 'data' => []];

while ($row = $stmtYear->fetch(PDO::FETCH_ASSOC)) {
    $dataYear['labels'][] = $row['an_nais'];
    $dataYear['data'][] = (int) $row['count'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pie Chart Préchargé</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Répartition des personnes impliquées dans un accident</h2>
    <button id="filterSex">Afficher par Sexe</button>
    <button id="filterYear">Afficher par Année de Naissance</button>
    <div>
    <canvas id="pieChart" width="400" height="400"></canvas>
    </div>
    <script>
        // Données préchargées depuis PHP
        const dataSex = <?php echo json_encode($dataSex); ?>;
        const dataYear = <?php echo json_encode($dataYear); ?>;

        let pieChart;
        const ctx = document.getElementById('pieChart').getContext('2d');

        // Initialisation du graphique
        function initChart(data) {
            pieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: ['#4CAF50', '#FFC107', '#FF5722', '#2196F3'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { enabled: true }
                    }
                }
            });
        }

        // Mise à jour du graphique
        function updateChart(data) {
            pieChart.data.labels = data.labels;
            pieChart.data.datasets[0].data = data.data;
            pieChart.update();
        }

        // Gestion des boutons
        document.getElementById('filterSex').addEventListener('click', () => updateChart(dataSex));
        document.getElementById('filterYear').addEventListener('click', () => updateChart(dataYear));

        // Initialisation avec les données par sexe
        initChart(dataSex);
    </script>
</body>
</html>
