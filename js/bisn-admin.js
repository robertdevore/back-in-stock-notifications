function showTab(event, tabId) {
    event.preventDefault();

    var tabContent = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabContent.length; i++) {
        tabContent[i].style.display = "none";
        tabContent[i].style.opacity = "0";
    }

    var tabs = document.getElementsByClassName("nav-tab");
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove("nav-tab-active");
    }

    var activeTab = document.getElementById(tabId);
    activeTab.style.display = "block";
    activeTab.style.opacity = "1";
    event.currentTarget.classList.add("nav-tab-active");

    localStorage.setItem("activeTab", tabId);
}

document.addEventListener("DOMContentLoaded", function () {
    var activeTab = localStorage.getItem("activeTab") || "dashboard";
    var tabContent = document.getElementById(activeTab);
    tabContent.style.display = "block";
    tabContent.style.opacity = "1";
    document.querySelector(`a[href="#${activeTab}"]`).click();
});
