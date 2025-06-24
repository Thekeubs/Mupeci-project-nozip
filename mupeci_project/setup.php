<?php
// Fichier de setup à la racine pour installation en un clic
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>MUPECI Setup</title>
<style>body{font-family:Arial;max-width:600px;margin:50px auto;padding:20px;background:#f8f9fa;}
.btn{display:inline-block;padding:15px 30px;background:#22c55e;color:white;text-decoration:none;border-radius:8px;font-weight:bold;margin:10px;}
.btn:hover{background:#16a34a;}</style></head><body>";

echo "<h1>🚀 Installation MUPECI</h1>";
echo "<p>Choisissez votre méthode d'installation :</p>";

echo "<div style='display:grid;gap:20px;margin:30px 0;'>";

echo "<div style='border:2px solid #22c55e;padding:20px;border-radius:10px;'>";
echo "<h3>⚡ Installation Express (Recommandée)</h3>";
echo "<p>Installation automatique complète en 30 secondes</p>";
echo "<a href='database/quick-setup.php' class='btn'>🚀 Installation Express</a>";
echo "</div>";

echo "<div style='border:2px solid #3b82f6;padding:20px;border-radius:10px;'>";
echo "<h3>🔧 Installation Détaillée</h3>";
echo "<p>Installation avec suivi étape par étape et diagnostics</p>";
echo "<a href='database/auto-install.php' class='btn' style='background:#3b82f6;'>📊 Installation Détaillée</a>";
echo "</div>";

echo "</div>";

echo "<div style='background:#f0f9ff;padding:15px;border-radius:8px;margin:20px 0;'>";
echo "<h4>📋 Pré-requis :</h4>";
echo "<ul>";
echo "<li>✅ XAMPP installé et démarré</li>";
echo "<li>✅ MySQL en cours d'exécution (vert dans XAMPP)</li>";
echo "<li>✅ Dossier mupeci/ dans htdocs/</li>";
echo "</ul>";
echo "</div>";

echo "<p style='text-align:center;color:#666;'>";
echo "Après l'installation, utilisez :<br>";
echo "<strong>marie@mupeci.com / password123</strong> (Réceptionniste)<br>";
echo "<strong>admin@mupeci.com / admin123</strong> (Admin - Code: MUPECI2024)";
echo "</p>";

echo "</body></html>";
?>
