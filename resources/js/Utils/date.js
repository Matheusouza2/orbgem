export const formatDateBR = (value) => {
    if (!value) return '';
    const [date] = String(value).split('T');
    const [year, month, day] = date.split('-');
    return year && month && day ? `${day}/${month}/${year}` : value;
};

export const formatDateTimeBR = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString('pt-BR');
};
