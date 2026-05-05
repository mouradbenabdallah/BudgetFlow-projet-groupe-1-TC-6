// JavaScript global BudgetFlow.
(function () {
  "use strict";

  document
    .querySelectorAll("[data-password-toggle]")
    .forEach(function (button) {
      button.addEventListener("click", function () {
        var target = document.getElementById(
          button.getAttribute("data-password-toggle"),
        );
        if (!target) {
          return;
        }

        var isPassword = target.getAttribute("type") === "password";
        target.setAttribute("type", isPassword ? "text" : "password");
        button.setAttribute(
          "aria-label",
          isPassword ? "Masquer le mot de passe" : "Afficher le mot de passe",
        );
        button.innerHTML = isPassword
          ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 3l18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 5.09A9.77 9.77 0 0112 4c5 0 9 5 9 8a10.88 10.88 0 01-2.2 3.43M6.1 6.1C4.22 7.43 3 9.78 3 12c0 3 4 8 9 8a9.65 9.65 0 004.08-.93" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
          : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
      });
    });

  if (typeof Chart !== "undefined") {
    Chart.defaults.color = "#718096";
    Chart.defaults.borderColor = "#e7efed";
    Chart.defaults.font.family = "Inter";
  }

  function tnd(value) {
    return (
      new Intl.NumberFormat("fr-FR", {
        maximumFractionDigits: 3,
        minimumFractionDigits: 3,
      }).format(Number(value) || 0) + " DT"
    );
  }

  function readJsonScript(id) {
    var script = document.getElementById(id);

    if (!script) {
      return [];
    }

    try {
      return JSON.parse(script.textContent || "[]");
    } catch (error) {
      return [];
    }
  }

  window.initMonthlyBars = function (
    canvasId,
    months,
    incomeData,
    expenseData,
  ) {
    var canvas = document.getElementById(canvasId);

    if (!canvas || typeof Chart === "undefined") {
      return null;
    }

    return new Chart(canvas, {
      type: "bar",
      data: {
        labels: months,
        datasets: [
          {
            label: "Revenus",
            data: incomeData,
            backgroundColor: "#007f5f",
            borderRadius: 6,
            barPercentage: 0.5,
            categoryPercentage: 0.7,
          },
          {
            label: "Dépenses",
            data: expenseData,
            backgroundColor: "#e5e9e7",
            borderRadius: 6,
            barPercentage: 0.5,
            categoryPercentage: 0.7,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            stacked: false,
            grid: {
              display: false,
            },
            ticks: {
              color: "#8ba0a3",
              font: {
                size: 12,
                weight: "500",
              },
            },
            border: {
              display: false,
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              color: "#f0f4f3",
              drawBorder: false,
            },
            ticks: {
              color: "#8ba0a3",
              font: {
                size: 11,
                weight: "500",
              },
              callback: function (value) {
                if (value >= 1000) {
                  return (value / 1000).toFixed(value % 1000 === 0 ? 0 : 1) + "k";
                }
                return value;
              },
            },
            border: {
              display: false,
            },
          },
        },
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              color: "#8ba0a3",
              boxWidth: 12,
              boxHeight: 12,
              padding: 20,
              font: {
                size: 12,
                weight: "500",
              },
              usePointStyle: true,
              pointStyle: "rectRounded",
            },
          },
          tooltip: {
            backgroundColor: "#002b36",
            borderColor: "#b9c9c6",
            borderWidth: 1,
            titleColor: "#ffffff",
            bodyColor: "#e8f2ef",
            padding: 12,
            cornerRadius: 8,
            callbacks: {
              label: function (context) {
                return (
                  " " + context.dataset.label + ": " + tnd(context.parsed.y)
                );
              },
            },
          },
        },
      },
    });
  };

  window.initDoughnutChart = function (canvasId, labels, data, colors) {
    var canvas = document.getElementById(canvasId);

    if (!canvas || typeof Chart === "undefined") {
      return null;
    }

    return new Chart(canvas, {
      type: "doughnut",
      data: {
        labels: labels,
        datasets: [
          {
            data: data,
            backgroundColor: colors,
            borderColor: "#ffffff",
            borderWidth: 4,
            hoverOffset: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "65%",
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            backgroundColor: "#002b36",
            borderColor: "#b9c9c6",
            borderWidth: 1,
            titleColor: "#ffffff",
            bodyColor: "#e8f2ef",
            padding: 10,
            cornerRadius: 8,
            callbacks: {
              label: function (context) {
                return " " + context.label + ": " + tnd(context.parsed);
              },
            },
          },
        },
      },
    });
  };

  document.addEventListener("DOMContentLoaded", function () {
    var evolutionData = readJsonScript("bf-evolution-chart-data");
    var chartData = readJsonScript("bf-category-chart-data");

    if (document.getElementById("monthlyChart")) {
      window.initMonthlyBars(
        "monthlyChart",
        evolutionData.map(function (item) {
          return item.month;
        }),
        evolutionData.map(function (item) {
          return Number(item.income);
        }),
        evolutionData.map(function (item) {
          return Number(item.expense);
        }),
      );
    }

    if (document.getElementById("categoryChart") && chartData.length > 0) {
      window.initDoughnutChart(
        "categoryChart",
        chartData.map(function (item) {
          return item.name;
        }),
        chartData.map(function (item) {
          return Number(item.amount);
        }),
        chartData.map(function (item) {
          return item.color || "#718096";
        }),
      );
    }
  });

  // === Categories Page ===
  function initCategoriesPage() {
    var filterButtons = document.querySelectorAll('[data-filter]');
    var categoryCards = document.querySelectorAll('[data-category-type]');
    var searchInput = document.getElementById('categorySearch');
    var createPicker = document.getElementById('create-color-picker');
    var editPicker = document.getElementById('edit-color-picker');
    var colorInput = document.getElementById('category-color');
    var editColorInput = document.getElementById('edit-category-color');
    var createModalEl = document.getElementById('createCategoryModal');
    var editModalEl = document.getElementById('editCategoryModal');
    var editButtons = document.querySelectorAll('[data-category-edit]');
    var currentFilter = 'all';

    function setActivePicker(picker, value) {
      if (!picker) return;
      picker.querySelectorAll('.bf-color-btn-dark').forEach(function (button) {
        button.classList.toggle('is-active', button.dataset.categoryColor === value);
      });
    }

    function bindPicker(picker, input) {
      if (!picker || !input) return;
      picker.querySelectorAll('.bf-color-btn-dark').forEach(function (button) {
        button.addEventListener('click', function () {
          input.value = button.dataset.categoryColor;
          setActivePicker(picker, button.dataset.categoryColor);
        });
      });
    }

    bindPicker(createPicker, colorInput);
    bindPicker(editPicker, editColorInput);

    if (createPicker && colorInput) {
      setActivePicker(createPicker, colorInput.value);
    }

    filterButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        filterButtons.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        currentFilter = btn.dataset.filter;
        filterCategories();
      });
    });

    function filterCategories() {
      categoryCards.forEach(function (card) {
        var type = card.dataset.categoryType;
        var name = (card.dataset.categoryName || '').toLowerCase();
        var searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        var show = true;
        if (currentFilter !== 'all' && type !== currentFilter) {
          show = false;
        }
        if (searchTerm && name.indexOf(searchTerm) === -1) {
          show = false;
        }
        card.style.display = show ? '' : 'none';
      });
    }

    if (searchInput) {
      searchInput.addEventListener('input', filterCategories);
    }

    editButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var catId = btn.dataset.categoryEdit;
        var catName = btn.dataset.categoryName;
        var catColor = btn.dataset.categoryColor;
        document.getElementById('edit-category-id').value = catId;
        document.getElementById('edit-category-name').value = catName;
        document.getElementById('edit-category-color').value = catColor;
        if (editPicker) {
          setActivePicker(editPicker, catColor);
        }
        var editInstance = bootstrap.Modal.getOrCreateInstance(editModalEl);
        editInstance.show();
      });
    });

    // Auto-open modals from data attributes (replaces PHP-injected JS)
    var autoOpen = document.getElementById('bf-auto-open-modal');
    if (autoOpen) {
      var target = autoOpen.dataset.target;
      var modalEl = document.getElementById(target);
      if (modalEl) {
        var instance = bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.show();
      }
    }
  }

  // === Transactions Index Page ===
  function initTransactionsIndexPage() {
    document.querySelectorAll('.bf-type-toggle-dark').forEach(function (button) {
      button.addEventListener('click', function () {
        document.querySelectorAll('.bf-type-toggle-dark').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById('transaction-type-input').value = this.dataset.type;
      });
    });

    var filtersToggle = document.getElementById('filtersToggle');
    var advancedFilters = document.getElementById('advancedFilters');
    if (filtersToggle && advancedFilters) {
      filtersToggle.addEventListener('click', function () {
        advancedFilters.style.display = advancedFilters.style.display === 'none' ? '' : 'none';
        filtersToggle.classList.toggle('is-active');
      });
    }

    var searchInput = document.getElementById('transactionSearch');
    var filterType = document.getElementById('filterType');
    var filterCategory = document.getElementById('filterCategory');
    var filterMonth = document.getElementById('filterMonth');
    var rows = document.querySelectorAll('.bf-transactions-table tbody tr');

    function filterRows() {
      var searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
      var typeVal = filterType ? filterType.value : '';
      var catVal = filterCategory ? filterCategory.value : '';
      var monthVal = filterMonth ? filterMonth.value : '';
      rows.forEach(function (row) {
        var text = row.textContent.toLowerCase();
        var rowType = row.dataset.type || '';
        var rowCat = row.dataset.category || '';
        var rowDate = (row.querySelector('.bf-transaction-date') || {}).textContent || '';
        var show = true;
        if (searchTerm && text.indexOf(searchTerm) === -1) { show = false; }
        if (typeVal && rowType !== typeVal) { show = false; }
        if (catVal && rowCat !== catVal) { show = false; }
        if (monthVal && rowDate.indexOf(monthVal) !== 0) { show = false; }
        row.style.display = show ? '' : 'none';
      });
    }

    if (searchInput) { searchInput.addEventListener('input', filterRows); }
    if (filterType) { filterType.addEventListener('change', filterRows); }
    if (filterCategory) { filterCategory.addEventListener('change', filterRows); }
    if (filterMonth) { filterMonth.addEventListener('change', filterRows); }
  }

  // === Transaction Form Page ===
  function initTransactionFormPage() {
    document.querySelectorAll('.bf-page-transactions-form .bf-type-toggle').forEach(function (button) {
      button.addEventListener('click', function () {
        var group = button.closest('.bf-type-toggle-group');
        if (!group) return;
        group.querySelectorAll('.bf-type-toggle').forEach(function (b) { b.classList.remove('is-active'); });
        button.classList.add('is-active');
        var hiddenInput = document.getElementById('transaction-type');
        if (hiddenInput) {
          hiddenInput.value = button.dataset.transactionType;
        }
      });
    });

    var categorySelect = document.getElementById('transaction-category');
    var preview = document.getElementById('category-preview');
    var previewDot = document.getElementById('category-preview-dot');
    var previewLabel = document.getElementById('category-preview-label');

    function updatePreview() {
      if (!categorySelect || !preview || !previewDot || !previewLabel) return;
      var option = categorySelect.options[categorySelect.selectedIndex];
      var color = option && option.dataset.color ? option.dataset.color : '#8B90A7';
      var label = option ? option.textContent.trim() : 'Sans catégorie';
      preview.style.display = 'inline-flex';
      previewDot.style.backgroundColor = color;
      previewLabel.textContent = label;
    }

    if (categorySelect) {
      categorySelect.addEventListener('change', updatePreview);
      updatePreview();
    }
  }

  // === Confirm Dialogs (replaces onclick handlers) ===
  function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        var message = el.dataset.confirm || 'Are you sure?';
        if (!confirm(message)) {
          e.preventDefault();
        }
      });
    });
  }

  // Run page-specific inits
  if (document.querySelector('.bf-page-categories-index')) {
    initCategoriesPage();
  }
  if (document.querySelector('.bf-page-transactions-index')) {
    initTransactionsIndexPage();
  }
  if (document.querySelector('.bf-page-transactions-form')) {
    initTransactionFormPage();
  }
  initConfirmDialogs();
})();
