// Simple LocalStorage store for beneficiaries (JS only)

const KEY = 'xpm_beneficiaries_v1';

function read() {
  try {
    return JSON.parse(localStorage.getItem(KEY) || '[]') || [];
  } catch {
    return [];
  }
}
function write(all) {
  localStorage.setItem(KEY, JSON.stringify(all));
}

export function useBeneficiaries() {
  function list(filter) {
    const all = read();
    if (!filter) return all;
    return all.filter((b) =>
      (filter.service ? b.service === filter.service : true) &&
      (filter.kind ? b.kind === filter.kind : true) &&
      (filter.providerId ? b.providerId === filter.providerId : true)
    );
  }

  function add(b) {
    const all = read();
    const id = Math.random().toString(36).slice(2);
    const item = { ...b, id, createdAt: Date.now() };

    const exists = all.find(
      (x) =>
        x.value === item.value &&
        x.service === item.service &&
        x.kind === item.kind &&
        x.providerId === item.providerId
    );
    if (!exists) {
      all.unshift(item);
      write(all);
    }
    return item;
  }

  function remove(id) {
    write(read().filter((x) => x.id !== id));
  }

  return { list, add, remove };
}
