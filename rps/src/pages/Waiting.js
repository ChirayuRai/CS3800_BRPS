import React from "react";
import "../styles/Waiting.css";
import { useNavigate } from "react-router-dom";

function Waiting() {
  let navigate = useNavigate();

  function dim(e) {
    e.target.style.background = "#4C8950";
  }
  function bright(e) {
    e.target.style.background = "#4CAF50";
  }

  function gray(e) {
    e.target.style.background = "Gray";
  }

  return (
    <div className="Whole">
      <h1 className="head-text">Rooms you could join</h1>
      <div className="Rooms">
        <button
          className="room-btn"
          onMouseEnter={dim}
          onMouseLeave={bright}
          onClick={() => {
            navigate("/R1");
          }}
        >
          Room 1
        </button>
        <button
          className="room-btn"
          onMouseEnter={dim}
          onMouseLeave={bright}
          onClick={() => {
            navigate("/R2");
          }}
        >
          Room 2
        </button>
        <button
          className="room-btn"
          onMouseEnter={dim}
          onMouseLeave={bright}
          onClick={() => {
            navigate("/R3");
          }}
        >
          Room 3
        </button>
        <button
          className="room-btn"
          onMouseEnter={dim}
          onMouseLeave={bright}
          onClick={() => {
            navigate("/R4");
          }}
        >
          Room 4
        </button>
        <button
          className="room-btn"
          onMouseEnter={dim}
          onMouseLeave={bright}
          onClick={() => {
            navigate("/R5");
          }}
        >
          Room 5
        </button>
      </div>
    </div>
  );
}

export default Waiting;
