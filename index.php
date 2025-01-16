<?php
// Veritabanı bağlantısı
$host = 'localhost';
$dbname = '100days_challenge';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Kullanıcı IP adresini alarak kullanıcıyı tanımlıyoruz
$user_id = $_SERVER['REMOTE_ADDR'];

// Eğer bir POST isteği geldiyse işaretli günleri kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completed_days'])) {
    $completed_days = $_POST['completed_days']; // JSON formatında gelen günler

    // Veritabanında kullanıcı kaydı var mı kontrol et
    $stmt = $pdo->prepare("SELECT * FROM progress WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->rowCount() > 0) {
        // Kayıt varsa güncelle
        $stmt = $pdo->prepare("UPDATE progress SET completed_days = ? WHERE user_id = ?");
        $stmt->execute([$completed_days, $user_id]);
    } else {
        // Kayıt yoksa ekle
        $stmt = $pdo->prepare("INSERT INTO progress (user_id, completed_days) VALUES (?, ?)");
        $stmt->execute([$user_id, $completed_days]);
    }
    echo "Progress saved.";
    exit;
}

// Kullanıcıya ait günleri ve hedef gün sayısını alıyoruz
$stmt = $pdo->prepare("SELECT completed_days FROM progress WHERE user_id = ?");
$stmt->execute([$user_id]);
$completed_days = $stmt->fetchColumn() ?: '[]';

$target_days = 100; // Varsayılan hedef gün sayısı
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_days'])) {
    $target_days = (int)$_POST['target_days'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Days Challenge</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: rgb(32, 32, 60);
            color: white;
            overflow-y: auto;
            text-align: center;
        }

        h1 {
            text-align: center;
            margin-top: 20px;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
            gap: 10px;
            max-width: 90%;
            margin: 20px auto;
        }

        .day {
            width: 40px;
            height: 40px;
            border: 2px solid black;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
        }

        .day.completed {
            background-color: green;
            color: white;
        }

        .confetti {
            position: absolute;
            width: 5px;
            height: 5px;
            border-radius: 100%;
            animation: 700ms fall ease-in-out;
            opacity: 0;
        }

        @keyframes fall {
            0% {
                opacity: 1;
            }
            100% {
                transform: translateY(100px);
                opacity: 0;
            }
        }

        .form-container {
            margin: 20px;
        }

        .form-container input {
            padding: 10px;
            font-size: 16px;
            border-radius: 90px;
        }

        .form-container button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 90px;
        }

    </style>
</head>
<body>
    <div class="form-container">
        <form method="POST">
            <label for="target_days">Select target number of days: </label>
            <input type="number" name="target_days" id="target_days" min="1" value="<?php echo $target_days; ?>" required>
            <button type="submit">save</button>
        </form>
    </div>

    <h1><?php echo $target_days; ?> Days Challenge</h1>
    <div class="container" id="days-container"></div>

    <script>
        // Confetti etkisi
        document.addEventListener("mousemove", function (event) {
            for (let i = 0; i < 5; i++) {
                const confetti = document.createElement("div");
                confetti.classList.add("confetti");
                document.body.appendChild(confetti);

                const randomX = Math.floor(Math.random() * 30);
                const randomY = Math.floor(Math.random() * 30);

                confetti.style.left = event.clientX + randomX + "px";
                confetti.style.top = event.clientY + randomY + "px";

                const randomColor = Math.floor(Math.random() * 256);
                confetti.style.backgroundColor = "rgb(256, 256, " + randomColor + ")";

                setTimeout(() => {
                    document.body.removeChild(confetti);
                }, 500);
            }
        });

        const completedDays = JSON.parse(<?php echo json_encode($completed_days); ?>);
        const targetDays = <?php echo $target_days; ?>;

        const container = document.getElementById('days-container');

        // Dinamik olarak kutucukları oluştur
        for (let i = 1; i <= targetDays; i++) {
            const day = document.createElement('div');
            day.classList.add('day');
            day.innerText = i;
            day.setAttribute('data-day', i);

            if (completedDays.includes(i.toString())) {
                day.classList.add('completed');
            }

            day.addEventListener('click', () => {
                day.classList.toggle('completed');
                saveProgress();
            });

            container.appendChild(day);
        }

        function saveProgress() {
            const completedDays = [];
            document.querySelectorAll('.day.completed').forEach(day => {
                completedDays.push(day.getAttribute('data-day'));
            });

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'completed_days=' + JSON.stringify(completedDays)
            }).then(response => response.text()).then(data => {
                console.log(data);
            });
        }
    </script>
</body>
</html>
