document.addEventListener("DOMContentLoaded", function() {
  // Load Header
  fetch("components/header.html")
    .then(response => response.text())
    .then(data => {
      document.getElementById("header-placeholder").innerHTML = data;
      checkUserAccountStatus(); 
    });

  // Load Footer
  fetch("components/footer.html")
    .then(response => response.text())
    .then(data => {
      const footerPlaceholder = document.getElementById("footer-placeholder");
      if(footerPlaceholder) footerPlaceholder.innerHTML = data;
    });
});

function checkUserAccountStatus() {
  const accountBtn = document.getElementById("accountBtn");
  if (!accountBtn) return;

  const user = JSON.parse(localStorage.getItem("user"));
  if (user) {
    accountBtn.textContent = "My Account";
    accountBtn.href = "account.html"; 
  } else {
    accountBtn.textContent = "Get Started";
    accountBtn.href = "registration.html";
  }
}