// Menggunakan relative path agar fleksibel sesuai dengan folder hosting Anda
const BASE_URL = "../backend";

// ─── LOGIN ────────────────────────────────────────────────────────────────────

$("#btn-login").on("click", function () {
  const username = $("#username").val().trim();
  const password = $("#password").val().trim();

  $.ajax({
    url: BASE_URL + "/api_login.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({ username, password }),
    success: function (res) {
      if (res.success) {
        $("#page-login").hide();
        $("#page-main").show();
        $("#label-user").text("👤 " + res.user);
        loadMovies(); // langsung load semua movie
      } else {
        $("#login-error").text(res.message).show();
      }
    },
    error: function (xhr) {
      let message = "Gagal terhubung ke server.";
      if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
      } else {
        const reqUrl = new URL(BASE_URL + "/api_login.php", window.location.href).href;
        message += ` (Status: ${xhr.status}, URL: ${reqUrl})`;
      }
      $("#login-error").text(message).show();
    }
  });
});

// ─── LOGOUT ───────────────────────────────────────────────────────────────────

$("#btn-logout").on("click", function () {
  $("#page-main").hide();
  $("#page-login").show();
  $("#username, #password").val("");
  $("#movies-result").empty();
});

// ─── GET ALL MOVIES ───────────────────────────────────────────────────────────

$("#btn-get").on("click", function () {
  $("#search-input").val("");
  loadMovies();
});

function loadMovies(keyword = "") {
  const url = keyword
    ? BASE_URL + "/api_movies.php?search=" + encodeURIComponent(keyword)
    : BASE_URL + "/api_movies.php";

  $.ajax({
    url: url,
    method: "GET",
    success: function (res) {
      renderMovies(res.data);
    },
    error: function (xhr) {
      const reqUrl = new URL(url, window.location.href).href;
      $("#movies-result").html(`<p class="empty">Gagal memuat data. (Status: ${xhr.status}, URL: ${reqUrl})</p>`);
    }
  });
}

function renderMovies(movies) {
  if (!movies || movies.length === 0) {
    $("#movies-result").html('<p class="empty">Tidak ada movie ditemukan.</p>');
    return;
  }

  let html = '<table><thead><tr><th>Poster</th><th>#</th><th>Judul</th><th>Genre</th><th>Tahun</th></tr></thead><tbody>';
  movies.forEach(function (m) {
    let posterHtml = '';
    if (m.image) {
      posterHtml = `<img src="${BASE_URL}/${m.image}" class="movie-thumbnail" alt="${m.title}">`;
    } else {
      posterHtml = `<div class="movie-thumbnail-placeholder">🎬</div>`;
    }
    
    html += `<tr>
      <td style="width: 70px; text-align: center; vertical-align: middle;">${posterHtml}</td>
      <td>${m.id}</td>
      <td style="font-weight: 500;">${m.title}</td>
      <td><span class="genre-badge">${m.genre}</span></td>
      <td>${m.year}</td>
    </tr>`;
  });
  html += '</tbody></table>';
  $("#movies-result").html(html);
}

// ─── SEARCH MOVIES ────────────────────────────────────────────────────────────

$("#btn-search").on("click", function () {
  const keyword = $("#search-input").val().trim();
  loadMovies(keyword);
});

// Bisa juga tekan Enter di input search
$("#search-input").on("keypress", function (e) {
  if (e.which === 13) $("#btn-search").trigger("click");
});

// ─── ADD MOVIE ────────────────────────────────────────────────────────────────

$("#btn-add").on("click", function () {
  const title = $("#add-title").val().trim();
  const genre = $("#add-genre").val().trim();
  const year  = parseInt($("#add-year").val());
  const imageFile = $("#add-image")[0].files[0];

  $("#add-error, #add-success").hide();

  if (!title || !genre || !year) {
    $("#add-error").text("Semua field wajib diisi (kecuali poster).").show();
    return;
  }

  // Create FormData object to send multipart data (text fields + image file)
  const formData = new FormData();
  formData.append("title", title);
  formData.append("genre", genre);
  formData.append("year", year);
  if (imageFile) {
    formData.append("image", imageFile);
  }

  $.ajax({
    url: BASE_URL + "/api_movies.php",
    method: "POST",
    data: formData,
    processData: false, // Prevent jQuery from processing the data
    contentType: false, // Prevent jQuery from setting contentType
    success: function (res) {
      if (res.success) {
        $("#add-success").text(res.message).show();
        $("#add-title, #add-genre, #add-year, #add-image").val(""); // Clear all inputs
        loadMovies(); // refresh tabel
      } else {
        $("#add-error").text(res.message).show();
      }
    },
    error: function (xhr) {
      let message = "Gagal terhubung ke server.";
      if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
      } else {
        const reqUrl = new URL(BASE_URL + "/api_movies.php", window.location.href).href;
        message += ` (Status: ${xhr.status}, URL: ${reqUrl})`;
      }
      $("#add-error").text(message).show();
    }
  });
});
