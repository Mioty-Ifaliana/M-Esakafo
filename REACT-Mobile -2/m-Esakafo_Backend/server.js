const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');

const app = express();

// Middleware pour logger toutes les requêtes
app.use((req, res, next) => {
    console.log(`[${new Date().toISOString()}] ${req.method} ${req.url} - IP: ${req.ip}`);
    next();
});

// Configuration CORS
app.use(cors({
    origin: '*',
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization']
}));

app.use(express.json());

// Base de données
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'm_esakafo'
});

db.connect((err) => {
    if (err) {
        console.error('Erreur de connexion à la base de données:', err);
        return;
    }
    console.log('Connecté à la base de données MySQL');
});

// Route principale
app.get('/', (req, res) => {
    res.json({ 
        message: 'API M-Esakafo en ligne',
        endpoints: {
            plats: '/api/plats'
        }
    });
});

// Route pour les plats
app.get('/api/plats', async (req, res) => {
    console.log('Requête reçue pour /api/plats');
    
    try {
        const [results] = await db.promise().query('SELECT * FROM plats');
        const plats = results.map(plat => ({
            id: plat.id,
            nom: plat.nom || '',
            sprite: plat.sprite || '',
            temps_cuisson: plat.temps_cuisson || '',
            prix: plat.prix || '0'
        }));
        
        console.log(`Envoi de ${plats.length} plats`);
        res.json(plats);
    } catch (error) {
        console.error('Erreur SQL:', error);
        res.status(500).json({ 
            error: 'Erreur serveur', 
            details: error.message 
        });
    }
});

// Gestion des 404
app.use((req, res) => {
    res.status(404).json({ 
        error: 'Route non trouvée',
        path: req.path
    });
});

// Démarrage du serveur
const PORT = 3000;
app.listen(PORT, '0.0.0.0', () => {
    console.log(`\n=== Serveur démarré sur le port ${PORT} ===`);
    console.log('URLs disponibles:');
    console.log(`- http://localhost:${PORT}/api/plats`);
});
