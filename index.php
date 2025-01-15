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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

// Kullanıcıya ait günleri veritabanından çekme
$stmt = $pdo->prepare("SELECT completed_days FROM progress WHERE user_id = ?");
$stmt->execute([$user_id]);
$completed_days = $stmt->fetchColumn() ?: '[]';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>100 Days Challenge</title>
    <script src="script.js" defer></script>

    <style>
        body {
    margin: 0;
    padding: 0;
    overflow: hidden;
    background-color: rgb(32, 32, 60);
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
 

h1 {
    text-align: center;
    margin-top: 20px;
}



        .container {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 10px;
            width: 500px;
            margin: 0 auto;
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
        }
        .day.completed {
            background-color: green;
            color: white;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">100 Days Challenge</h1>
    <div class="container" id="days-container"></div>

    <script>
        document.addEventListener("mousemove", function (event) {
  for (const i = 0; i < 5; i++) {
    const confetti = document.createElement("div")
    confetti.classList.add("confetti")
    document.body.appendChild(confetti)

    const randomX = Math.floor(Math.random() * 30)
    const randomY = Math.floor(Math.random() * 30)

    confetti.style.left = event.clientX + randomX + "px"
    confetti.style.top = event.clientY + randomY + "px"

    const randomColor = Math.floor(Math.random() * 256)
    confetti.style.backgroundColor = "rgb(256, 256, " + randomColor + ")"

    // const randomAngle = Math.floor(Math.random() * 360)
    // confetti.style.transform = `rotate(${randomAngle}deg)`

    // const randomSkew = Math.floor(Math.random() * 10)
    // confetti.style.transform += `skew(${randomSkew}deg, ${randomSkew}deg)`

    setInterval(() => {
      document.body.removeChild(confetti)
    }, 500)
  }
})

        // PHP'den gelen JSON verisini alıyoruz .. confet işlemi bitti
        const completedDays = JSON.parse(<?php echo json_encode($completed_days); ?>);

        const container = document.getElementById('days-container');

        // 100 tane kutucuk oluştur
        for (let i = 1; i <= 100; i++) {
            const day = document.createElement('div');
            day.classList.add('day');
            day.innerText = i;
            day.setAttribute('data-day', i);

            // Eğer gün işaretlenmişse "completed" sınıfını ekle
            if (completedDays.includes(i.toString())) {
                day.classList.add('completed');
            }

            day.addEventListener('click', () => {
                day.classList.toggle('completed');
                saveProgress();
            });

            container.appendChild(day);
        }

        // İşaretlenen günleri sunucuya kaydetme
        function saveProgress() {
            const completedDays = [];
            document.querySelectorAll('.day.completed').forEach(day => {
                completedDays.push(day.getAttribute('data-day'));
            });

            // Veriyi sunucuya gönder
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'completed_days=' + JSON.stringify(completedDays)
            }).then(response => response.text()).then(data => {
                console.log(data); // Sunucudan gelen yanıtı konsola yazdır
            });
        }
    </script>
</body>
</html>
