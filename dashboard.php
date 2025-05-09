<?php
session_start();
include("connect.php");

// Check if user is logged in
if(!isset($_SESSION['email'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

// Get user info
$email = $_SESSION['email'];
$query = mysqli_query($conn, "SELECT * FROM `users` WHERE email='$email'");
$user = mysqli_fetch_assoc($query);
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Social Media Campaign Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- PapaParse for CSV parsing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
  </head>
  <body class="bg-gray-100">
    <div class="flex h-screen">
      <!-- Sidebar -->
      <div class="w-64 bg-bslue-800 text-white p-5">
        <h2 class="text-2xl font-bold mb-6">Dashboard</h2>
        
        <ul>
  <li>
    <a href="#" class="block py-2 px-4 hover:bg-gradient-to-r hover:from-[#c15252] hover:to-[#4ed7e6]">Overview</a>
  </li>
  <li>
    <a href="#" class="block py-2 px-4 hover:bg-blue-700">Analytics</a>
  </li>
  <li>
    <a href="#" class="block py-2 px-4 hover:bg-blue-700">Settings</a>
  </li>
  <li>
    <a href="logout.php" class="block py-2 px-4 hover:bg-blue-700">Logout</a>
  </li>
</ul>
      </div>

      <!-- Main Content -->
      <div class="flex-1 p-6">
      <h1 class="text-3xl font-semibold text-center mb-8 text-blue-800">
  Welcome, <?php echo $user['firstName'].' '.$user['lastName']; ?> - Social Media Campaign Dashboard
</h1>
        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white p-6 rounded-lg shadow-lg">
            <h5 class="text-xl font-semibold mb-2 text-blue-600">
              Engagement Rate
            </h5>
            <h2 id="engagementRate" class="text-4xl font-bold">0%</h2>
          </div>
          <div class="bg-white p-6 rounded-lg shadow-lg">
            <h5 class="text-xl font-semibold mb-2 text-blue-600">
              Follower Growth
            </h5>
            <h2 id="followerGrowth" class="text-4xl font-bold">0</h2>
          </div>
          <div class="bg-white p-6 rounded-lg shadow-lg">
            <h5 class="text-xl font-semibold mb-2 text-blue-600">
              Total Reach
            </h5>
            <h2 id="totalReach" class="text-4xl font-bold">0</h2>
          </div>
          <div class="bg-white p-6 rounded-lg shadow-lg">
            <h5 class="text-xl font-semibold mb-2 text-blue-600">
              Impressions
            </h5>
            <h2 id="impressions" class="text-4xl font-bold">0</h2>
          </div>
        </div>

        <!-- Table for displaying CSV data -->
        <div class="overflow-x-auto bg-white p-6 rounded-lg shadow-lg mb-8">
          <table class="min-w-full table-auto">
            <thead>
              <tr>
                <th class="py-2 px-4 border-b text-left">Item</th>
                <th class="py-2 px-4 border-b text-left">Value</th>
              </tr>
            </thead>
            <tbody id="campaignTableBody">
              <!-- Data rows will be inserted here -->
            </tbody>
          </table>
        </div>

        <!-- Chart section for visualizing selected data -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <h3 class="text-xl font-semibold text-center mb-6 text-blue-600">
            Campaign Performance Metrics
          </h3>
          <div class="max-w-full">
            <canvas id="metricsChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <script>
      // CSV URL from published Google Sheet
      const csvUrl =
        "https://docs.google.com/spreadsheets/d/e/2PACX-1vTRPGzw3oJYU6jpFOZkWinqUETFocvRqWHEEna-2MK_XpYYy57GzMdm2JPs-bt6mQoWCOZqprRZhX7_/pub?output=csv";

      Papa.parse(csvUrl, {
        download: true,
        header: false, // Change to true if your CSV includes header row
        complete: function (results) {
          const tableBody = document.getElementById("campaignTableBody");
          const metrics = {};
          let engagementRate = 0;
          let followerGrowth = 0;
          let totalReach = 0;
          let impressions = 0;

          // Process each row of the CSV

          results.data.forEach((row, index) => {
            if (index > 0 && row.length >= 2) {
              // Skip the header row and ensure the row has at least two columns
              const item = row[0].trim().toLowerCase(); // Extract and normalize the item name
              const value = parseFloat(row[1]) || row[1].trim(); // Convert to number if possible, otherwise trim the string

              // Insert rows into the table
              const newRow = tableBody.insertRow();
              const cellItem = newRow.insertCell(0);
              const cellValue = newRow.insertCell(1);
              cellItem.textContent = item;
              cellValue.textContent = value;

              // Collect metrics for the chart and separate values for count-up
              if (item === "engagement rate") {
                engagementRate = parseFloat(value) || 0;
              } else if (item === "follower growth") {
                followerGrowth = parseFloat(value) || 0;
              } else if (
                ["total reached", "reach", "impressions"].includes(item)
              ) {
                metrics[item] = parseFloat(value) || 0;
              }
            }
          });

          // Update metrics display
          document.getElementById(
            "engagementRate"
          ).textContent = `${engagementRate}%`;
          document.getElementById("followerGrowth").textContent =
            followerGrowth;
          document.getElementById("totalReach").textContent = totalReach;
          document.getElementById("impressions").textContent = impressions;

          // Draw chart
          drawChart(metrics);
        },
        error: function (error) {
          console.error("Error fetching or parsing the CSV data:", error);
        },
      });

      // Function to draw a chart using Chart.js
      function drawChart(metrics) {
        const ctx = document.getElementById("metricsChart").getContext("2d");
        new Chart(ctx, {
          type: "bar",
          data: {
            labels: Object.keys(metrics),
            datasets: [
              {
                label: "Campaign Metrics",
                data: Object.values(metrics),
                backgroundColor: "rgba(54, 162, 235, 0.6)",
                borderColor: "rgba(54, 162, 235, 1)",
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
              y: {
                beginAtZero: true,
              },
            },
          },
        });
      }
    </script>
  </body>
</html>
