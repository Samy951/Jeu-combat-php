<?php
session_start();

class Personnage {
    protected $nom;
    protected $pointsDeVie;
    protected $pointsDeVieMax;
    protected $attaqueMin;
    protected $attaqueMax;

    public function __construct($nom, $pointsDeVie, $attaqueMin, $attaqueMax) {
        $this->nom = $nom;
        $this->pointsDeVie = $pointsDeVie;
        $this->pointsDeVieMax = $pointsDeVie;
        $this->attaqueMin = $attaqueMin;
        $this->attaqueMax = $attaqueMax;
    }

    public function getNom() {
        return $this->nom;
    }

    public function getPointsDeVie() {
        return $this->pointsDeVie;
    }

    public function attaquer($cible) {
        $degats = rand($this->attaqueMin, $this->attaqueMax);
        $cible->subirDegats($degats);
        echo "<p>{$this->nom} attaque {$cible->getNom()} et lui inflige {$degats} points de dégâts.</p>";
    }

    public function subirDegats($degats) {
        $this->pointsDeVie -= $degats;
        if ($this->pointsDeVie < 0) {
            $this->pointsDeVie = 0;
        }
    }
}

class Guerrier extends Personnage {
    private $defenseMin;
    private $defenseMax;

    public function __construct($nom, $pointsDeVie, $attaqueMin, $attaqueMax, $defenseMin, $defenseMax) {
        parent::__construct($nom, $pointsDeVie, $attaqueMin, $attaqueMax);
        $this->defenseMin = $defenseMin;
        $this->defenseMax = $defenseMax;
    }

    public function utiliserDefense() {
        $defense = rand($this->defenseMin, $this->defenseMax);
        $this->pointsDeVie += $defense;
        if ($this->pointsDeVie > $this->pointsDeVieMax) {
            $this->pointsDeVie = $this->pointsDeVieMax;
        }
        echo "<p>{$this->nom} utilise Défense et récupère {$defense} points de vie.</p>";
    }
}

class Magicien extends Personnage {
    private $capaciteSpeciale;
    private $capaciteCooldown;
    private $isSleeping;

    public function __construct($nom, $pointsDeVie, $attaqueMin, $attaqueMax, $capaciteSpeciale, $capaciteCooldown) {
        parent::__construct($nom, $pointsDeVie, $attaqueMin, $attaqueMax);
        $this->capaciteSpeciale = $capaciteSpeciale;
        $this->capaciteCooldown = $capaciteCooldown;
        $this->isSleeping = false;
    }

    public function utiliserCapaciteSpeciale($cible) {
        if (!$this->isSleeping) {
            $this->isSleeping = true;
            echo "<p>{$this->nom} utilise {$this->capaciteSpeciale} et endort {$cible->getNom()} pendant un tour.</p>";
            $cible->subirDegats($this->attaqueMax);
        } else {
            echo "<p>{$this->nom} ne peut pas utiliser sa capacité spéciale. Il est déjà endormi.</p>";
        }
    }

    public function updateSleepAbility() {
        $this->isSleeping = false;
        echo "<p>{$this->nom} se réveille.</p>";
    }
}

// Création des personnages
$guerrier = new Guerrier("Guerrier", 100, 20, 40, 10, 19);
$magicien = new Magicien("Magicien", 100, 5, 10, "Endormissement", 2 * 60);

// Vérification de l'état du jeu
if (!isset($_SESSION['guerrier']) || !isset($_SESSION['magicien'])) {
    // Nouvelle partie, initialisation des personnages
    $_SESSION['guerrier'] = $guerrier;
    $_SESSION['magicien'] = $magicien;
} else {
    // Récupération des personnages depuis la session
    $guerrier = $_SESSION['guerrier'];
    $magicien = $_SESSION['magicien'];
}

// Vérification de l'action du joueur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['personnage'])) {
        $action = $_POST['action'];
        $personnage = $_POST['personnage'];

        // Vérification du personnage choisi
        if ($personnage === "guerrier") {
            $attaquant = $guerrier;
            $defenseur = $magicien;
        } else {
            $attaquant = $magicien;
            $defenseur = $guerrier;
        }

        // Exécution de l'action choisie
        if ($action === "attaquer") {
            $attaquant->attaquer($defenseur);
        } elseif ($action === "defense" && $attaquant instanceof Guerrier) {
            $attaquant->utiliserDefense();
        } elseif ($action === "capacite" && $attaquant instanceof Magicien) {
            $attaquant->utiliserCapaciteSpeciale($defenseur);
        }

        // Vérification de la condition de victoire ou défaite
        if ($guerrier->getPointsDeVie() === 0 || $magicien->getPointsDeVie() === 0) {
            echo "<p>Le jeu est terminé !</p>";
            echo "<p><a href='reset.php'>Rejouer</a></p>";
            session_destroy();
        } else {
            // Mise à jour de l'état du jeu dans la session
            $_SESSION['guerrier'] = $guerrier;
            $_SESSION['magicien'] = $magicien;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Jeu de combat</title>
</head>
<body>
<h1>Jeu de combat</h1>

<?php if (!isset($_SESSION['guerrier']) || !isset($_SESSION['magicien'])): ?>
    <p>Nouvelle partie</p>
    <form action="" method="post">
        <p>Choisissez votre personnage :</p>
        <label for="guerrier">
            <input type="radio" id="guerrier" name="personnage" value="guerrier" required>
            Guerrier
        </label>
        <label for="magicien">
            <input type="radio" id="magicien" name="personnage" value="magicien" required>
            Magicien
        </label>
        <br>
        <button type="submit" name="action" value="jouer">Jouer</button>
    </form>
<?php else: ?>
    <p>Points de vie restants :</p>
    <p>Guerrier : <?php echo $guerrier->getPointsDeVie(); ?></p>
    <p>Magicien : <?php echo $magicien->getPointsDeVie(); ?></p>

    <form action="" method="post">
        <p>Choisissez votre action :</p>
        <?php if ($guerrier->getPointsDeVie() > 0 && $magicien->getPointsDeVie() > 0): ?>
            <input type="radio" id="attaquer" name="action" value="attaquer" required>
            <label for="attaquer">Attaquer</label>
            <br>
            <?php if ($guerrier instanceof Guerrier): ?>
                <input type="radio" id="defense" name="action" value="defense" required>
                <label for="defense">Utiliser Défense</label>
                <br>
            <?php elseif ($magicien instanceof Magicien): ?>
                <input type="radio" id="capacite" name="action" value="capacite" required>
                <label for="capacite">Utiliser Capacité Spéciale</label>
                <br>
            <?php endif; ?>
            <br>
            <button type="submit" name="personnage" value="guerrier">Guerrier</button>
            <button type="submit" name="personnage" value="magicien"> Magicien</button>
        <?php endif; ?>
    </form>
<?php endif; ?>
</body>
</html>
