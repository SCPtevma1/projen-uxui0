// 1. ตั้งค่าการเชื่อมต่อกับ Appwrite Server
    const client = new Appwrite.Client()
        .setEndpoint('https://appwrite.io') 
        .setProject('6aa96d3d003b19f5f05b');  
     

    const account = new Appwrite.Account(client);

    // 2. เมื่อผู้ใช้คลิกปุ่ม "Sign in with Google"
    document.getElementById('google-login-btn').addEventListener('click', () => {
        account.createOAuth2Token(
            'google', 
            'http://localhost/projen-uxui0/index.php', // ลิงก์เมื่อเข้าสู่ระบบสำเร็จ
            'http://localhost/projen-uxui0/index.php'  // ลิงก์กรณีเกิดข้อผิดพลาด
        );
    });
