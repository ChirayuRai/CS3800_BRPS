import React from 'react';
import './App.css';

function App() {

  function dim(e) {
    e.target.style.background='#4C8950';
  }
  function bright(e) {
    e.target.style.background='#4CAF50';
  }

  return (
    <div className="Whole-thing">
      <div className="res-box">
        <div className="Result">
          <p>HAHA I'M SO COOL</p>
        </div>
      </div>
      <div className="Buttons">
        <button className='btn' onMouseEnter={dim} onMouseLeave={bright}>Rock</button>
        <button className='btn' onMouseEnter={dim} onMouseLeave={bright}>Paper</button>
        <button className='btn' onMouseEnter={dim} onMouseLeave={bright}>Scissors</button>
      </div>

    </div>
  );
}

export default App;
