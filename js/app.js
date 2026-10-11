document.querySelectorAll('a[href]').forEach((link) => {
  link.addEventListener('click', () => {
    document.body.classList.add('is-leaving');
  });
});

const cardContainer = document.querySelector('.coco');

if (cardContainer) {
  const cards = Array.from(cardContainer.children);
  const cardContent = [
    ['วิธีการสั่งซื้อ', 'รู้จักขั้นตอนการสั่งซื้อเว็บไซต์ UX/UI ตั้งแต่เริ่มพูดคุยจนถึงรับผลงานพร้อมใช้งาน', 'page-01.html'],
    ['เกี่ยวกับเรา', 'ทำความรู้จักทีมงาน แนวคิด และประสบการณ์ในการออกแบบเว็บไซต์', 'page-02.html'],
    ['บริการ', 'เลือกบริการออกแบบ UX/UI และรูปแบบเว็บไซต์ที่เหมาะกับความต้องการ', 'page-03.html'],
    ['ผลงาน', 'ชมตัวอย่างผลงานและแนวทางการออกแบบจากโปรเจกต์ที่ผ่านมา', 'page-04.html'],
    ['บทความ', 'อ่านความรู้และแนวคิดที่ช่วยให้การออกแบบเว็บไซต์มีประสิทธิภาพ', 'page-05.html'],
    ['ข่าวสาร', 'ติดตามข่าวสารและอัปเดตใหม่จากทีมงานของเรา', 'page-06.html'],
    ['แกลเลอรี', 'รวมภาพและผลงานที่น่าสนใจไว้ให้เลือกชม', 'page-07.html'],
    ['ทีมงาน', 'รู้จักผู้ดูแลและผู้สร้างสรรค์งานออกแบบของเรา', 'page-08.html'],
    ['คำถามที่พบบ่อย', 'รวมคำตอบสำหรับคำถามที่ลูกค้ามักสอบถามก่อนเริ่มงาน', 'page-09.html'],
    ['ติดต่อเรา', 'ส่งรายละเอียดงานหรือสอบถามข้อมูลเพิ่มเติมกับทีมงาน', 'page-10.html'],
    ['กิจกรรม', 'ดูข่าวสารกิจกรรมและเรื่องราวที่น่าสนใจจากเรา', 'page-11.html'],
    ['ทรัพยากร', 'แหล่งข้อมูลและเครื่องมือที่ช่วยต่อยอดงานออกแบบ', 'page-12.html'],
    ['โครงการ', 'ดูรายละเอียดโครงการและขั้นตอนการทำงานของเรา', 'page-13.html'],
    ['ปฏิทิน', 'วางแผนกำหนดการและติดตามช่วงเวลาสำคัญของงาน', 'page-14.html'],
    ['สมาชิก', 'พื้นที่สำหรับข้อมูลและสิทธิประโยชน์ของสมาชิก', 'page-15.html'],
    ['บัญชีผู้ใช้', 'จัดการข้อมูลส่วนตัวและรายละเอียดบัญชีของคุณ', 'page-16.html'],
    ['การตั้งค่า', 'ปรับแต่งการใช้งานให้เหมาะกับความต้องการของคุณ', 'page-17.html'],
    ['ช่วยเหลือ', 'รับคำแนะนำและความช่วยเหลือเมื่อพบปัญหาการใช้งาน', 'page-18.html'],
    ['สรุปข้อมูล', 'รวมข้อมูลสำคัญเพื่อให้ดูภาพรวมได้อย่างรวดเร็ว', 'page-19.html']
  ];

  for (let index = cardContent.length - 1; index > 0; index -= 1) {
    const randomIndex = Math.floor(Math.random() * (index + 1));
    [cardContent[index], cardContent[randomIndex]] = [cardContent[randomIndex], cardContent[index]];
  }

  cards.forEach((card, index) => {
    const [title, description, link] = cardContent[index];
    const bodyParagraphs = card.querySelectorAll('.info-card__body > p');
    card.querySelector('h2').textContent = title;
    bodyParagraphs[1].textContent = description;
    card.querySelector('.info-card__button').href = `html2/${link}`;
  });
}

const slideImage = document.querySelector('[data-slide-image]');
const slideCounter = document.querySelector('[data-slide-counter]');
const slideCaption = document.querySelector('[data-slide-caption]');
const thumbnails = document.querySelectorAll('[data-slide]');
const previousButton = document.querySelector('.hon__control--previous');
const nextButton = document.querySelector('.hon__control--next');

if (slideImage && slideCounter && slideCaption && thumbnails.length && previousButton && nextButton) {
  const slides = [
    {
      image: 'ralf1403-hiking-10374972_1920.jpg',
      alt: 'นักเดินป่าบนเส้นทางธรรมชาติ',
      caption: 'ออกเดินทางไปพบมุมมองใหม่'
    },
    {
      image: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1400&q=85',
      alt: 'ภูเขาและเส้นทางเดินป่า',
      caption: 'ธรรมชาติรอให้เราออกไปค้นพบ'
    },
    {
      image: 'https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1400&q=85',
      alt: 'วิวภูเขาท่ามกลางแสงแดด',
      caption: 'เก็บความทรงจำระหว่างทาง'
    }
  ];
  let currentSlide = 0;

  const showSlide = (slideIndex) => {
    currentSlide = (slideIndex + slides.length) % slides.length;
    const slide = slides[currentSlide];
    slideImage.src = slide.image;
    slideImage.alt = slide.alt;
    slideCaption.textContent = slide.caption;
    slideCounter.textContent = `${currentSlide + 1} / ${slides.length}`;

    thumbnails.forEach((thumbnail, index) => {
      const isActive = index === currentSlide;
      thumbnail.classList.toggle('is-active', isActive);
      thumbnail.setAttribute('aria-selected', isActive.toString());
    });
  };

  thumbnails.forEach((thumbnail) => {
    thumbnail.addEventListener('click', () => showSlide(Number(thumbnail.dataset.slide)));
  });
  previousButton.addEventListener('click', () => showSlide(currentSlide - 1));
  nextButton.addEventListener('click', () => showSlide(currentSlide + 1));
}
