new Swiper(".wrapper", {
  loop: true,
  spaceBetween: 30,
  // Autoplay
  autoplay: {
    delay: 5000,
    disableOnInteraction: false,
    pauseOnMouseEnter: true,
  },
  // Pagination bullets
  pagination: {
    el: ".swiper-pagination",
    clickable: true,
    dynamicBullets: true,
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
  breakpoints: {
    0: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    1024: {
      slidesPerView: 3,
    },
  },
});

const slides = Array.from(document.querySelectorAll('.slider-image'));
if (slides.length > 0) {
  let currentIndex = 0;

  function showSlide(index) {
    currentIndex = (index + slides.length) % slides.length;

    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === currentIndex);
    });

  }

  setInterval(() => {
    showSlide(currentIndex + 1);
  }, 4000);
}


    async function loadComments() {
      const container = document.getElementById('comments-list');
      try {
        const res = await fetch('../api_comments.php?page=student');
        const data = await res.json();
        if (data.success && data.comments && data.comments.length > 0) {
          container.innerHTML = data.comments.map(c => {
            const stars = '⭐'.repeat(c.rating || 5);
            const initial = (c.user_name || 'U').charAt(0).toUpperCase();
            return `
              <div class="comment-card">
                <div>
                  <div class="comment-header-row">
                    <div class="comment-avatar">${initial}</div>
                    <div>
                      <h4 class="comment-author">${escapeHtml(c.user_name)}</h4>
                      <div class="comment-stars">${stars}</div>
                    </div>
                  </div>
                  <p class="comment-body">${escapeHtml(c.comment)}</p>
                </div>
                <div class="comment-date">
                  <i class="ph ph-clock"></i> ${escapeHtml(c.created_formatted || '')}
                </div>
              </div>
            `;
          }).join('');
        } else {
          container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 25px;">ยังไม่มีความคิดเห็น มาร่วมเป็นคนแรกที่เขียนรีวิวเลย!</div>';
        }
      } catch (err) {
        container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #94a3b8; padding: 20px;">ไม่สามารถโหลดความคิดเห็นได้ในขณะนี้</div>';
      }
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
      })[m]);
    }

    // จัดการการส่งฟอร์มความคิดเห็น
    document.getElementById('commentForm').addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitComment');
      const alertBox = document.getElementById('commentAlert');
      const userName = document.getElementById('userName').value.trim();
      const rating = document.getElementById('userRating').value;
      const comment = document.getElementById('userComment').value.trim();

      if (!userName || !comment) return;

      btn.disabled = true;
      btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> กำลังส่งความคิดเห็น...';
      alertBox.style.display = 'none';

      try {
        const res = await fetch('../api_comments.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_name: userName, rating: parseInt(rating), comment: comment, page: 'student' })
        });
        const result = await res.json();
        if (result.success) {
          alertBox.className = 'comment-alert success';
          alertBox.innerHTML = '✓ ' + result.message;
          alertBox.style.display = 'block';
          document.getElementById('commentForm').reset();
          loadComments(); // รีเฟรชรายการคอมเมนต์ทันที
        } else {
          alertBox.className = 'comment-alert error';
          alertBox.innerHTML = '✕ ' + (result.error || 'เกิดข้อผิดพลาดในการส่งข้อมูล');
          alertBox.style.display = 'block';
        }
      } catch (err) {
        alertBox.className = 'comment-alert error';
        alertBox.innerHTML = '✕ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
        alertBox.style.display = 'block';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ph ph-paper-plane-right"></i> ส่งความคิดเห็น';
      }
    });

    // ฟังก์ชันกรองรายวิชาตามระดับการเรียนรู้
    function filterByLevel(lvl) {
      const tabs = document.querySelectorAll('.level-tab');
      tabs.forEach(t => t.classList.remove('active'));
      if (window.event && window.event.currentTarget) {
        window.event.currentTarget.classList.add('active');
      }

      const groups = document.querySelectorAll('.level-group');
      groups.forEach(g => {
        if (lvl === 'all' || g.getAttribute('data-level') === lvl) {
          g.style.display = 'block';
        } else {
          g.style.display = 'none';
        }
      });
    }

    document.addEventListener('DOMContentLoaded', loadComments);