import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, Image, StyleSheet, SafeAreaView } from 'react-native';
import { fetchPlats } from '../services/api';

const HomeScreen = () => {
  const [plats, setPlats] = useState([]);

  useEffect(() => {
    loadPlats();
  }, []);

  const loadPlats = async () => {
    const data = await fetchPlats();
    setPlats(data);
  };

  // Define static image imports at the top level
  const images = {
    'spaghetti.jpg': require('../img/spaghetti.jpg'),
    'frite.jpg': require('../img/frite.jpg'),
    'steak.jpg': require('../img/steak.jpg'),
    'brochette.jpg': require('../img/brochette.jpg'),
  };

  const getImageSource = (imageName) => {
    const image = images[imageName];
    if (!image) {
      console.warn('Image non trouvée:', imageName);
      return null;
    }
    return image;
  };

  const renderItem = ({ item }) => (
    <View style={styles.platItem}>
      <Image 
        source={getImageSource(item.sprite)}
        style={styles.platImage}
      />
      <View style={styles.platInfo}>
        <Text style={styles.platNom}>{item.nom}</Text>
        <Text style={styles.platPrix}>{item.prix} Ar</Text>
        <Text style={styles.platTemps}>{item.temps_cuisson}</Text>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.header}>Nos Plats</Text>
      <FlatList
        data={plats}
        renderItem={renderItem}
        keyExtractor={item => item.id.toString()}
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
    padding: 10
  },
  header: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 20,
    textAlign: 'center'
  },
  platItem: {
    flexDirection: 'row',
    padding: 10,
    marginBottom: 10,
    backgroundColor: '#f8f8f8',
    borderRadius: 10,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 1,
    },
    shadowOpacity: 0.22,
    shadowRadius: 2.22,
  },
  platImage: {
    width: 80,
    height: 80,
    borderRadius: 10
  },
  platInfo: {
    marginLeft: 15,
    justifyContent: 'center',
    flex: 1
  },
  platNom: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 5
  },
  platPrix: {
    fontSize: 16,
    color: 'green',
    marginBottom: 5
  },
  platTemps: {
    fontSize: 14,
    color: '#666'
  }
});

export default HomeScreen;
