<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== COMMITTING FINAL OPTIMIZED CATEGORY IMAGES ===\n";

echo "Staging files...\n";
echo shell_exec("git add assets/images/categories/anaj.png assets/images/categories/bakery.png assets/images/categories/cold_drinks.png assets/images/categories/cosmetics.png assets/images/categories/ice_cream.png assets/images/categories/milk.png assets/images/categories/sauce.png assets/images/categories/snacks.png copy_assets.php 2>&1") . "\n";

echo "Git Status:\n";
echo shell_exec("git status 2>&1") . "\n";

echo "Committing...\n";
echo shell_exec("git commit -m \"Optimize and compress all category icons to lightweight true PNGs\" 2>&1") . "\n";

echo "Pushing...\n";
echo shell_exec("git push origin main 2>&1") . "\n";

echo "Done.\n";
?>
