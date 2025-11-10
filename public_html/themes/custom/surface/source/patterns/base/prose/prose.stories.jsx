import prose from "./prose.twig";

const settings = {
  title: "Base/Prose",
};

export const Prose = {
  render: (args) => prose(args),
};

export default settings;
