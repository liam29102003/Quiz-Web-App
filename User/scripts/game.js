// ans = ;
// incorrectAns = ["Paris", "Rome", "Madrid"];

// Set initial values
let countdownValue = 40;
let countdownTime = 3;

// Get references to HTML elements
const countdownElement = document.getElementById("timer");
const countdownMessage = document.getElementById("countdown");
const questionContainer = document.getElementById("question-container");
const countdownDisplay = document.getElementById("countdownDisplay");
window.onload = function() {
  startCountdown();
  startCountdown1();
};

// window.onload = startCountdown1;

let countdownInterval; // Declare it globally to access in other functions

function startCountdown1() {
  // Prevent multiple intervals if the function is called more than once
  if (countdownInterval) clearInterval(countdownInterval);

  countdownInterval = setInterval(() => {
    fetch("utils/get_timer.php")
      .then((res) => res.json())
      .then((data) => {
        if (!data || typeof data.remaining_time === "undefined") {
          console.error("Invalid response:", data);
          return;
        }

        countdownDisplay.textContent = data.remaining_time + "s";

        if (data.remaining_time <= 0) {
          clearInterval(countdownInterval); // Stop the interval when time runs out
          showTimesUpModal();
          sendAnswerToServer("");
        }
      })
      .catch((err) => console.error("Error fetching timer:", err));
  }, 1000);
}


function startCountdown() {
  startCountdown1();
  const countdownInterval = setInterval(() => {
    countdownTime--;
    countdownElement.textContent = countdownTime;
    if (countdownTime === 0) {
      clearInterval(countdownInterval);
      countdownElement.textContent = "Go!";
      setTimeout(() => {
        countdownMessage.style.display = "none";
        questionContainer.style.display = "block";
      }, 1000);
    }
  }, 1000);
}

function showCorrectModal(category) {
  document.getElementById("categoryModal").classList.remove("hidden");

  // Send request to PHP to delete the Redis timer
  fetch("utils/stop_timer.php", { method: "POST" })
    .then((response) => response.json())
    .then((data) => console.log(data.message))
    .catch((error) => console.error("Error:", error));
}

function closeModal() {
  document.getElementById("categoryModal").classList.add("hidden");
}

function showIncorrectModal() {
  // document.getElementById("correctAnswerText").textContent = correctAnswer;
  document.getElementById("incorrectModal").classList.remove("hidden");
  // Send request to PHP to delete the Redis timer
  fetch("utils/stop_timer.php", { method: "POST" })
    .then((response) => response.json())
    .then((data) => console.log(data.message))
    .catch((error) => console.error("Error:", error));
}

function closeIncorrectModal() {
  document.getElementById("incorrectModal").classList.add("hidden");
}

function showTimesUpModal() {
  timesUpModal.classList.remove("hidden");
  // Send request to PHP to delete the Redis timer
  fetch("utils/stop_timer.php", { method: "POST" })
    .then((response) => response.json())
    .then((data) => console.log(data.message))
    .catch((error) => console.error("Error:", error));
}

function closeTimesUpModal() {
  timesUpModal.classList.add("hidden");
}

function disableAnswer() {
  let incorrectAnswers = document.querySelectorAll(
    ".incorrect-answer:not(.hidden)"
  );

  if (incorrectAnswers.length > 0) {
    let randomIndex = Math.floor(Math.random() * incorrectAnswers.length);
    let hiddenAnswer = incorrectAnswers[randomIndex];

    hiddenAnswer.classList.add("hidden"); // Hide the answer
    document.getElementById("disable-btn").classList.add("hidden"); // Disable the button

    // Send to PHP for Redis storage
    fetch("utils/store_hidden_answer.php", {
      method: "POST",
      body: JSON.stringify({ answer: hiddenAnswer.id }),
      headers: { "Content-Type": "application/json" },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          document.getElementById("coinCount").innerText =
            data.updated_coins;

          // Disable the button if coins are below 30
          let button = document.getElementById("disable-btn");
          button.disabled = data.updated_coins < 30;
        }
      })
      .catch((error) => console.error("Error:", error));
  }
}

function goToNextQuestion() {
  location.reload();
  fetch("utils/delete_hidden_answer.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
  });
}

function skipQuestion() {
  // document.getElementById("disable-btn1").classList.add("hidden"); // Disable the button
  fetch("utils/update_coins.php", {
      method: "POST",
      body: JSON.stringify({ user_id: 1, amount: 100 }), // Deduct 100 coins
      headers: { "Content-Type": "application/json" }
  })
  .then(response => response.json())
  .then(data => {
      if (data.status === "success") {
          // Update UI with new coin value
          document.getElementById("coinCount").textContent = data.updated_coins;
          goToNextQuestion(); // Move to next question after updating coins
      } else {
          alert("Not enough coins to skip!");
      }
  })
  .catch(error => console.error("Error:", error));


  fetch("utils/stop_timer.php", { method: "POST" })
    .then((response) => response.json())
    .then((data) => console.log(data.message))
    .catch((error) => console.error("Error:", error));
}


document.querySelectorAll(".ans-btn").forEach((button) => {
  button.addEventListener("click", handleAnswerClick);
});

function handleAnswerClick(event) {
  let chosenAnswer = event.target.textContent.trim();
  stopQuizTimer(); // Stop the quiz timer (assumed to stop the countdown interval)

  sendAnswerToServer(chosenAnswer);

  // Clear the countdown interval to stop fetching remaining time
  if (countdownInterval) {
    clearInterval(countdownInterval); // Stop the countdown
  }
}


function stopQuizTimer() {
  fetch("utils/stop_timer.php", {
    method: "POST"
  })
  .then(response => response.json())
  .then(data => console.log(data.message)) // Log response
  .catch(error => console.error("Error stopping timer:", error));
}


function sendAnswerToServer(answer) {
  stopQuizTimer();
  fetch("utils/store_answer.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ answer: answer }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data.message);
      if (data.is_correct) {
        showCorrectModal("History");
      } else {
        showIncorrectModal();
      }
    })
    .catch((error) => console.log("Error:", error));
}
