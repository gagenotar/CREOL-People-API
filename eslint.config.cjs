const { FlatCompat } = require('@eslint/eslintrc');
const compat = new FlatCompat({
  recommendedConfig: {
    root: true
  }
});

module.exports = compat.extends('./.eslintrc.json');
