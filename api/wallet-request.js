// wallet-request.js

// Simulated JWT token (replace this with your actual one)
const token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoiMSIsImV4cCI6MTc1MzUyMzcxMn0.NNUY4_gi5sq6JZtp_qxSEU-BevGJS9CA797a4Gr8j10";  // Example token

// API URL — adjust if your local server uses a different port
const url = "http://localhost/vaultwise/api/wallet.php";

// Send request
fetch(url, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "Authorization": `Bearer ${token}`  // This must be exact: no extra quotes
  },
  body: JSON.stringify({})  // You can pass data here if your wallet.php expects any
})
  .then(response => {
    if (!response.ok) {
      throw new Error("HTTP error: " + response.status);
    }
    return response.json();
  })
  .then(data => {
    console.log("Wallet API response:", data);
  })
  .catch(error => {
    console.error("Request failed:", error);
  });
