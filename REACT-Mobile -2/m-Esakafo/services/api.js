// URL de l'API
const API_URL = 'http://192.168.88.17:3000';

export const fetchPlats = async () => {
    try {
        console.log('Récupération des plats...');
        const response = await fetch(`${API_URL}/api/plats`);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const plats = await response.json();
        console.log(`${plats.length} plats récupérés`);
        return plats;
    } catch (error) {
        console.error('Erreur:', error.message);
        return [];
    }
};
