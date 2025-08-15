const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { check, validationResult } = require('express-validator');

// Mongoose Modeli
const User = require('../models/User');

// @route   POST api/register
// @desc    Yeni kullanıcı kaydı
// @access  Public
router.post(
  '/register',
  [
    check('email', 'Geçerli bir e-posta adresi girin').isEmail(),
    check('username', 'Kullanıcı adı gerekli').not().isEmpty(),
    check('password', 'Lütfen en az 6 karakterli bir şifre girin').isLength({ min: 6 }),
  ],
  async (req, res) => {
    // Giriş verilerindeki hataları kontrol et
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { email, username, password } = req.body;

    try {
      // E-posta adresinin zaten var olup olmadığını kontrol et
      let user = await User.findOne({ email });
      if (user) {
        return res.status(400).json({ msg: 'Bu e-posta adresi zaten kullanılıyor.' });
      }

      // Yeni bir kullanıcı oluştur
      user = new User({
        email,
        username,
        password,
      });

      // Şifreyi hashle
      const salt = await bcrypt.genSalt(10);
      user.password = await bcrypt.hash(password, salt);

      // Kullanıcıyı veritabanına kaydet
      await user.save();

      // JSON Web Token (JWT) oluştur ve döndür
      const payload = {
        user: {
          id: user.id,
        },
      };

      jwt.sign(
    payload,
    process.env.JWT_SECRET, // Değiştirilen kısım
    { expiresIn: 360000 },
    (err, token) => {
        if (err) throw err;
        res.json({ token });
    }
);          res.json({ token });
        }
      );
    } catch (err) {
      // **ÖNEMLİ DEĞİŞİKLİK:** Hatanın nedenini sunucu konsoluna yazdırıyoruz
      console.error(err.message);
      // Hatanın detaylarını istemciye vermekten kaçınıp, genel bir hata mesajı döndürüyoruz
      res.status(500).send('Sunucu hatası');
    }
  }
);

module.exports = router;
