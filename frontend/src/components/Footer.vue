<template>
  <footer class="footer">
    <div class="footer-content">
      <p class="footer-text">
        Разработка: <a href="https://t.me/blueberryMgn" target="_blank" rel="noopener noreferrer" class="footer-link">t.me/blueberryMgn</a>
      </p>
    </div>
  </footer>
</template>

<script setup>
// Компонент футера
</script>

<style scoped>
.footer {
  background-color: #222;
  color: #fff;
  padding: 20px;
  margin-top: auto;
}

.footer-content {
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
}

.footer-text {
  margin: 0;
  font-size: 14px;
  color: #ccc;
}

.footer-link {
  color: #3498db;
  text-decoration: none;
  transition: color 0.2s;
}

.footer-link:hover {
  color: #2980b9;
  text-decoration: underline;
}
</style>


