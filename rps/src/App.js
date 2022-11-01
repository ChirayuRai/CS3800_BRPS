import React from "react";
import R1 from "./pages/R1";
import R2 from "./pages/R2";
import R3 from "./pages/R3";
import R4 from "./pages/R4";
import R5 from "./pages/R5";
import { BrowserRouter as Router, Route, Routes } from "react-router-dom";
import Waiting from "./pages/Waiting";

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Waiting />} />
        <Route path="/R1" element={<R1 />} />
        <Route path="/R2" element={<R2 />} />
        <Route path="/R3" element={<R3 />} />
        <Route path="/R4" element={<R4 />} />
        <Route path="/R5" element={<R5 />} />
      </Routes>
    </Router>
  );
}

export default App;
